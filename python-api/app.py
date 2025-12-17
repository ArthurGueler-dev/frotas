"""
API Flask para Otimização de Rotas e Clustering Geográfico
Versão: 1.0.0 (2025-12-11)

Funcionalidades:
- Geocodificação com cache no MySQL (Nominatim)
- Clustering hierárquico para blocos compactos (diâmetro ≤ 5km)
- Otimização de rotas CVRP com PyVRP
- Distâncias reais via OSRM local
- Geração de mapas interativos com Folium
"""

import os
import json
import math
import time
import hashlib
from datetime import datetime
from typing import List, Dict, Tuple, Optional
from urllib.parse import quote_plus

from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import pandas as pd
import numpy as np
from scipy.cluster.hierarchy import linkage, fcluster
from scipy.spatial.distance import pdist, squareform
from geopy.geocoders import Nominatim
from geopy.exc import GeocoderTimedOut, GeocoderServiceError
import requests
import pymysql
from sqlalchemy import create_engine, text
import pyvrp
from pyvrp import Model
from pyvrp.stop import MaxRuntime
import folium
import polyline

# Configuração
app = Flask(__name__)
CORS(app)

# Configurações do banco de dados
DB_CONFIG = {
    'host': '187.49.226.10',
    'port': 3306,
    'user': 'f137049_tool',
    'password': 'In9@1234qwer',
    'database': 'f137049_in9aut',
    'charset': 'utf8mb4'
}

# OSRM local
OSRM_URL = "http://localhost:5001"

# Diretório para mapas gerados
MAPS_DIR = "static/maps"
os.makedirs(MAPS_DIR, exist_ok=True)

# Geocoder (Nominatim com user_agent)
geolocator = Nominatim(user_agent="frotas-in9automacao-v1.0", timeout=10)

# Engine SQLAlchemy (com escape de caracteres especiais na senha)
engine = create_engine(
    f"mysql+pymysql://{DB_CONFIG['user']}:{quote_plus(DB_CONFIG['password'])}@"
    f"{DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['database']}?charset=utf8mb4"
)


# ============== FUNÇÕES AUXILIARES ==============

def haversine_distance(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """
    Calcular distância entre dois pontos usando fórmula de Haversine.
    Retorna distância em km.
    """
    R = 6371  # Raio da Terra em km

    lat1_rad = math.radians(lat1)
    lat2_rad = math.radians(lat2)
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)

    a = (math.sin(dlat/2) ** 2 +
         math.cos(lat1_rad) * math.cos(lat2_rad) * math.sin(dlon/2) ** 2)
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))

    return R * c


def get_geocode_from_cache(address: str) -> Optional[Tuple[float, float]]:
    """
    Buscar coordenadas do cache (MySQL).
    Tabela: FF_GeocodingCache (address, latitude, longitude, created_at)
    """
    try:
        # Hash do endereço para busca eficiente
        address_hash = hashlib.md5(address.lower().strip().encode()).hexdigest()

        with engine.connect() as conn:
            # Criar tabela se não existir
            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS FF_GeocodingCache (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    address_hash VARCHAR(32) UNIQUE,
                    address TEXT,
                    latitude DOUBLE,
                    longitude DOUBLE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_hash (address_hash)
                )
            """))
            conn.commit()

            result = conn.execute(
                text("SELECT latitude, longitude FROM FF_GeocodingCache WHERE address_hash = :hash"),
                {"hash": address_hash}
            ).fetchone()

            if result:
                return (float(result[0]), float(result[1]))

    except Exception as e:
        print(f"⚠️ Erro ao buscar cache: {e}")

    return None


def save_geocode_to_cache(address: str, lat: float, lon: float):
    """Salvar coordenadas no cache"""
    try:
        address_hash = hashlib.md5(address.lower().strip().encode()).hexdigest()

        with engine.connect() as conn:
            conn.execute(text("""
                INSERT INTO FF_GeocodingCache (address_hash, address, latitude, longitude)
                VALUES (:hash, :address, :lat, :lon)
                ON DUPLICATE KEY UPDATE
                    latitude = :lat,
                    longitude = :lon
            """), {
                "hash": address_hash,
                "address": address,
                "lat": lat,
                "lon": lon
            })
            conn.commit()

    except Exception as e:
        print(f"⚠️ Erro ao salvar cache: {e}")


def geocode_address(address: str) -> Optional[Tuple[float, float]]:
    """
    Geocodificar endereço (com cache).
    Retorna (lat, lon) ou None.
    """
    # Tentar cache primeiro
    cached = get_geocode_from_cache(address)
    if cached:
        print(f"✅ Cache hit: {address[:50]}")
        return cached

    # Geocodificar com Nominatim
    try:
        time.sleep(1)  # Rate limit: 1 req/seg
        location = geolocator.geocode(address + ", Brasil")

        if location:
            lat, lon = location.latitude, location.longitude
            save_geocode_to_cache(address, lat, lon)
            print(f"🌍 Geocodificado: {address[:50]} -> ({lat:.6f}, {lon:.6f})")
            return (lat, lon)
        else:
            print(f"❌ Não encontrado: {address[:50]}")
            return None

    except (GeocoderTimedOut, GeocoderServiceError) as e:
        print(f"⚠️ Erro no geocoder: {e}")
        return None




def haversine_distance_vectorized(lat1, lon1, lat2, lon2):
    """
    Versão vetorizada da distância Haversine usando NumPy broadcasting.
    Aceita arrays ou escalares.
    """
    R = 6371  # Raio da Terra em km
    
    # Converter para radianos
    lat1, lon1, lat2, lon2 = map(np.radians, [lat1, lon1, lat2, lon2])
    
    # Diferenças
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    
    # Fórmula de Haversine
    a = np.sin(dlat/2)**2 + np.cos(lat1) * np.cos(lat2) * np.sin(dlon/2)**2
    c = 2 * np.arcsin(np.sqrt(a))
    
    return R * c


def create_distance_matrix_fast(coords):
    """
    Criar matriz de distâncias usando broadcasting do NumPy.
    Muito mais rápido que loops para muitos pontos.
    """
    n = len(coords)
    lats = coords[:, 0]
    lons = coords[:, 1]
    
    # Broadcast para criar matrizes n x n
    lat1 = lats[:, np.newaxis]  # Shape (n, 1)
    lon1 = lons[:, np.newaxis]
    lat2 = lats[np.newaxis, :]  # Shape (1, n)
    lon2 = lons[np.newaxis, :]
    
    return haversine_distance_vectorized(lat1, lon1, lat2, lon2)


def create_compact_clusters(locations: List[Dict], max_diameter_km: float = 5.0) -> List[List[int]]:
    """
    Criar clusters compactos usando clustering hierárquico.

    Args:
        locations: Lista de dicts com 'id', 'lat', 'lon'
        max_diameter_km: Diâmetro máximo do cluster em km

    Returns:
        Lista de clusters (cada cluster é lista de índices)
    """
    if len(locations) <= 1:
        return [[0]] if locations else []

    # Extrair coordenadas
    coords = np.array([[loc['lat'], loc['lon']] for loc in locations])

    # Calcular matriz de distâncias Haversine (vetorizado - muito mais rápido)
    dist_matrix = create_distance_matrix_fast(coords)

    # Condensed distance matrix para scipy
    condensed_dist = squareform(dist_matrix)

    # Linkage com 'complete' (garante diâmetro máximo)
    linkage_matrix = linkage(condensed_dist, method='complete')

    # Cortar em threshold = max_diameter_km
    cluster_labels = fcluster(linkage_matrix, t=max_diameter_km, criterion='distance')

    # Agrupar por cluster
    clusters_dict = {}
    for idx, label in enumerate(cluster_labels):
        if label not in clusters_dict:
            clusters_dict[label] = []
        clusters_dict[label].append(idx)

    clusters = list(clusters_dict.values())

    print(f"📊 Clustering: {len(locations)} locais -> {len(clusters)} clusters (diâmetro ≤ {max_diameter_km}km)")

    return clusters


def split_large_cluster(cluster_indices: List[int], locations: List[Dict], max_size: int = 5) -> List[List[int]]:
    """
    Dividir cluster grande em sub-clusters de até max_size locais.
    Usa proximidade geográfica para manter sub-clusters compactos.
    """
    if len(cluster_indices) <= max_size:
        return [cluster_indices]

    # Coordenadas do cluster
    cluster_coords = np.array([[locations[i]['lat'], locations[i]['lon']] for i in cluster_indices])

    # Número de sub-clusters necessários
    n_subclusters = math.ceil(len(cluster_indices) / max_size)

    # Usar k-means simples para dividir (baseado em proximidade)
    from scipy.cluster.vq import kmeans2
    centroids, labels = kmeans2(cluster_coords, n_subclusters, minit='points')

    # Agrupar por label
    subclusters = {}
    for idx, label in enumerate(labels):
        if label not in subclusters:
            subclusters[label] = []
        subclusters[label].append(cluster_indices[idx])

    result = list(subclusters.values())
    print(f"  └─ Dividido cluster de {len(cluster_indices)} em {len(result)} sub-clusters")

    return result


def get_coords_hash(lat1: float, lon1: float, lat2: float, lon2: float) -> str:
    """Gerar hash único para par de coordenadas (order-independent)"""
    coords = sorted([(lat1, lon1), (lat2, lon2)])
    key = f"{coords[0][0]:.6f},{coords[0][1]:.6f},{coords[1][0]:.6f},{coords[1][1]:.6f}"
    return hashlib.md5(key.encode()).hexdigest()


def get_osrm_distance_matrix(coords: List[Tuple[float, float]]) -> Optional[np.ndarray]:
    """
    Obter matriz de distâncias reais via OSRM local.

    Args:
        coords: Lista de (lat, lon)

    Returns:
        Matriz NxN com distâncias em metros, ou None se erro
    """
    result = get_osrm_matrices(coords)
    return result['distances'] if result else None


def get_osrm_matrices(coords: List[Tuple[float, float]]) -> Optional[Dict]:
    """
    Obter matrizes de distâncias E durações reais via OSRM local.

    Args:
        coords: Lista de (lat, lon)

    Returns:
        Dict com 'distances' (metros) e 'durations' (segundos), ou None se erro
    """
    try:
        # Formato OSRM: lon,lat;lon,lat;...
        coordinates_str = ";".join([f"{lon},{lat}" for lat, lon in coords])

        url = f"{OSRM_URL}/table/v1/driving/{coordinates_str}"
        params = {
            'annotations': 'distance,duration'
        }

        response = requests.get(url, params=params, timeout=30)
        response.raise_for_status()

        data = response.json()

        if data['code'] == 'Ok':
            # Retornar AMBAS as matrizes
            return {
                'distances': np.array(data['distances']),  # metros
                'durations': np.array(data['durations'])   # segundos
            }
        else:
            print(f"⚠️ OSRM error: {data.get('message', 'Unknown')}")
            return None

    except Exception as e:
        print(f"❌ Erro ao consultar OSRM: {e}")
        return None


def solve_cvrp(depot_coords: Tuple[float, float],
               locations: List[Dict],
               capacity: int = 5) -> Optional[Dict]:
    """
    Resolver CVRP (Capacitated Vehicle Routing Problem) usando PyVRP.

    Args:
        depot_coords: (lat, lon) da base
        locations: Lista de dicts com 'id', 'lat', 'lon'
        capacity: Capacidade máxima por veículo

    Returns:
        Dict com rotas otimizadas ou None se erro
    """
    try:
        # Preparar coordenadas (depot primeiro)
        all_coords = [depot_coords] + [(loc['lat'], loc['lon']) for loc in locations]

        # OSRM OBRIGATÓRIO - distâncias E durações reais por rodovias
        print(f"🚗 Calculando distâncias e tempos OSRM (real) para {len(all_coords)} pontos...")
        osrm_data = get_osrm_matrices(all_coords)

        if osrm_data is None:
            print("❌ OSRM falhou - não é possível prosseguir sem dados reais")
            return None

        distance_matrix = osrm_data['distances']  # metros
        duration_matrix = osrm_data['durations']  # segundos

        # Converter para inteiros (PyVRP trabalha com inteiros)
        distance_matrix_int = distance_matrix.astype(int)
        duration_matrix_int = duration_matrix.astype(int)

        # Criar modelo PyVRP
        model = Model()

        # Adicionar depot (índice 0)
        depot = model.add_depot(x=0, y=0)

        # Adicionar clientes (locais)
        clients = []
        for i, loc in enumerate(locations):
            client = model.add_client(
                x=0,  # PyVRP não usa coordenadas, usa matriz de distância
                y=0
            )
            clients.append(client)

        # Adicionar tipo de veículo
        vehicle_type = model.add_vehicle_type(
            capacity=capacity,
            num_available=math.ceil(len(locations) / capacity)  # Veículos suficientes
        )

        # Adicionar arestas com custos da matriz de distância
        # Arestas depot -> clients
        for i, client in enumerate(clients):
            # depot para client (índice na matriz: 0 -> i+1)
            model.add_edge(depot, client, distance=int(distance_matrix_int[0][i+1]))
            # client para depot
            model.add_edge(client, depot, distance=int(distance_matrix_int[i+1][0]))

        # Arestas entre clientes
        for i, client_i in enumerate(clients):
            for j, client_j in enumerate(clients):
                if i != j:
                    # client_i para client_j (índices na matriz: i+1 -> j+1)
                    model.add_edge(client_i, client_j, distance=int(distance_matrix_int[i+1][j+1]))

        # Resolver
        print(f"🧮 Resolvendo CVRP com PyVRP...")
        result = model.solve(stop=MaxRuntime(10.0), display=False)  # 10 segundos max

        if not result.is_feasible():
            print("❌ Solução CVRP não é feasível")
            return None

        # Extrair rotas
        routes = []
        total_distance = 0
        total_duration = 0  # segundos

        for route_idx, route in enumerate(result.best.routes()):
            # route.visits() retorna lista de índices dos clientes (não inclui depot)
            try:
                route_visits = [visit for visit in route.visits()]
                print(f"  Rota {route_idx}: visits = {route_visits}")
            except Exception as e:
                print(f"  ❌ Erro ao obter visits da rota {route_idx}: {e}")
                continue

            if not route_visits:
                continue

            # Calcular distância E duração da rota (depot -> locais -> depot)
            route_distance = 0
            route_duration = 0  # segundos de viagem
            prev_idx = 0  # depot

            route_location_ids = []
            for visit_idx in route_visits:
                # visit_idx é 1-based (PyVRP retorna índices começando em 1 para clientes)
                # Na matriz de distância: depot=0, client1=1, client2=2, etc
                # Então visit_idx JÁ É o índice correto na matriz
                route_distance += distance_matrix_int[prev_idx][visit_idx]
                route_duration += duration_matrix_int[prev_idx][visit_idx]
                prev_idx = visit_idx
                # Para pegar o location correto, precisa ser visit_idx - 1 (converter para 0-based)
                route_location_ids.append(locations[visit_idx - 1]['id'])

            # Retorno ao depot
            route_distance += distance_matrix_int[prev_idx][0]
            route_duration += duration_matrix_int[prev_idx][0]

            # Adicionar tempo de parada (5 minutos por local = 300 segundos)
            route_duration_with_stops = route_duration + (len(route_visits) * 300)

            total_distance += route_distance
            total_duration += route_duration_with_stops

            routes.append({
                'route_id': route_idx + 1,
                'locations': route_location_ids,
                'sequence': route_visits,
                'distance_meters': int(route_distance),
                'distance_km': round(route_distance / 1000, 2),
                'duration_seconds': int(route_duration),  # tempo só de viagem
                'duration_minutes': round(route_duration / 60, 1),  # tempo só de viagem
                'duration_with_stops_minutes': round(route_duration_with_stops / 60, 1)  # com paradas
            })

        return {
            'routes': routes,
            'total_distance_km': round(total_distance / 1000, 2),
            'total_duration_minutes': round(total_duration / 60, 1),  # com paradas incluídas
            'num_vehicles': len(routes)
        }

    except Exception as e:
        print(f"❌ Erro no CVRP: {e}")
        import traceback
        traceback.print_exc()
        return None


def get_osrm_route_geometry(coords: List[Tuple[float, float]]) -> Optional[List[List[float]]]:
    """
    Obter geometria da rota (polyline) via OSRM.

    Args:
        coords: Lista de (lat, lon) na ordem da rota

    Returns:
        Lista de [lat, lon] da polyline, ou None
    """
    try:
        # Formato OSRM: lon,lat;lon,lat;...
        coordinates_str = ";".join([f"{lon},{lat}" for lat, lon in coords])

        url = f"{OSRM_URL}/route/v1/driving/{coordinates_str}"
        params = {
            'overview': 'full',
            'geometries': 'polyline'
        }

        response = requests.get(url, params=params, timeout=30)
        response.raise_for_status()

        data = response.json()

        if data['code'] == 'Ok' and 'routes' in data and len(data['routes']) > 0:
            # Decodificar polyline
            encoded_polyline = data['routes'][0]['geometry']
            decoded = polyline.decode(encoded_polyline)
            return decoded

        return None

    except Exception as e:
        print(f"⚠️ Erro ao obter geometria OSRM: {e}")
        return None


def create_route_map(depot: Dict,
                     locations: List[Dict],
                     routes: List[Dict],
                     filename: str) -> str:
    """
    Criar mapa interativo com Folium.

    Args:
        depot: {'lat': x, 'lon': y, 'name': '...'}
        locations: Lista de dicts com 'id', 'lat', 'lon', 'name'
        routes: Lista de rotas do CVRP
        filename: Nome do arquivo HTML

    Returns:
        Path do arquivo gerado
    """
    # Centro do mapa (média das coordenadas)
    all_lats = [depot['lat']] + [loc['lat'] for loc in locations]
    all_lons = [depot['lon']] + [loc['lon'] for loc in locations]
    center_lat = sum(all_lats) / len(all_lats)
    center_lon = sum(all_lons) / len(all_lons)

    # Criar mapa
    m = folium.Map(
        location=[center_lat, center_lon],
        zoom_start=12,
        tiles='OpenStreetMap'
    )

    # Marcador do depot (verde)
    folium.Marker(
        location=[depot['lat'], depot['lon']],
        popup=f"<b>🏠 BASE</b><br>{depot.get('name', 'Depot')}",
        icon=folium.Icon(color='green', icon='home', prefix='fa')
    ).add_to(m)

    # Cores para rotas
    colors = ['blue', 'red', 'purple', 'orange', 'darkred', 'lightred',
              'darkblue', 'darkgreen', 'cadetblue', 'darkpurple']

    # Desenhar rotas
    for route in routes:
        color = colors[route['route_id'] % len(colors)]

        # Coordenadas da rota (depot -> locais -> depot)
        route_coords = [
            (depot['lat'], depot['lon'])
        ]

        for loc_id in route['locations']:
            loc = next((l for l in locations if l['id'] == loc_id), None)
            if loc:
                route_coords.append((loc['lat'], loc['lon']))

        route_coords.append((depot['lat'], depot['lon']))

        # Obter geometria real via OSRM (se disponível)
        geometry = get_osrm_route_geometry(route_coords)

        if geometry:
            # Usar geometria real
            folium.PolyLine(
                locations=geometry,
                color=color,
                weight=4,
                opacity=0.7,
                popup=f"Rota {route['route_id']}: {route['distance_km']}km"
            ).add_to(m)
        else:
            # Fallback: linha reta
            folium.PolyLine(
                locations=route_coords,
                color=color,
                weight=4,
                opacity=0.7,
                dash_array='10',
                popup=f"Rota {route['route_id']}: {route['distance_km']}km (linha reta)"
            ).add_to(m)

        # Marcadores dos locais desta rota
        for order, loc_id in enumerate(route['locations'], 1):
            loc = next((l for l in locations if l['id'] == loc_id), None)
            if loc:
                folium.Marker(
                    location=[loc['lat'], loc['lon']],
                    popup=f"<b>Parada {order}</b><br>{loc.get('name', f'Local {loc_id}')}<br>Rota {route['route_id']}",
                    icon=folium.Icon(color=color, icon='info-sign'),
                    tooltip=f"#{order}"
                ).add_to(m)

    # Salvar mapa
    filepath = os.path.join(MAPS_DIR, filename)
    m.save(filepath)

    print(f"🗺️ Mapa salvo: {filepath}")

    return filepath


def gerar_link_google_maps_exato(base_lat: float, base_lon: float,
                                   lista_locais_ordenados: List[Dict]) -> str:
    """
    Gera link do Google Maps com rota na ordem exata otimizada.

    Args:
        base_lat: Latitude da base (ponto de partida)
        base_lon: Longitude da base
        lista_locais_ordenados: Lista de dicionários com 'lat' e 'lon' na ordem otimizada

    Returns:
        URL completa do Google Maps para navegação

    Exemplo:
        >>> locais = [{"lat": -23.55, "lon": -46.63}, {"lat": -23.56, "lon": -46.64}]
        >>> link = gerar_link_google_maps_exato(-23.54, -46.62, locais)
    """
    if not lista_locais_ordenados:
        # Se não há locais, retorna apenas a base
        return f"https://www.google.com/maps/search/?api=1&query={base_lat},{base_lon}"

    # Origin = base
    origin = f"{base_lat},{base_lon}"

    # Destination = último local da lista
    ultimo_local = lista_locais_ordenados[-1]
    destination = f"{ultimo_local['lat']},{ultimo_local['lon']}"

    # Waypoints = todos os locais intermediários (exceto o último)
    waypoints_list = []
    for local in lista_locais_ordenados[:-1]:
        waypoints_list.append(f"{local['lat']},{local['lon']}")

    # Montar URL
    base_url = "https://www.google.com/maps/dir/?api=1"
    params = [
        f"origin={origin}",
        f"destination={destination}",
        f"travelmode=driving"
    ]

    if waypoints_list:
        waypoints_str = "|".join(waypoints_list)
        params.append(f"waypoints={waypoints_str}")

    url_completa = base_url + "&" + "&".join(params)

    return url_completa


# ============== ENDPOINTS ==============

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'online',
        'version': '1.0.0',
        'osrm_url': OSRM_URL,
        'timestamp': datetime.now().isoformat()
    })


@app.route('/otimizar', methods=['POST'])
def otimizar_rotas():
    """
    Endpoint principal para otimização de rotas.

    Body JSON:
    {
        "base": {
            "lat": -20.3155,
            "lon": -40.3128,
            "name": "Base i9 Engenharia"
        },
        "locais": [
            {"id": 1, "endereco": "Rua X, 123, Vitória-ES"},
            {"id": 2, "lat": -20.32, "lon": -40.31, "name": "Cliente Y"},
            ...
        ],
        "max_diameter_km": 5.0,     # Opcional (padrão: 5)
        "max_locais_por_rota": 5     # Opcional (padrão: 5)
    }
    """
    try:
        data = request.get_json()

        # Validar entrada
        if not data or 'base' not in data or 'locais' not in data:
            return jsonify({
                'success': False,
                'error': 'Campos obrigatórios: base, locais'
            }), 400

        base = data['base']
        locais_input = data['locais']
        max_diameter_km = data.get('max_diameter_km', 5.0)
        max_locais_por_rota = data.get('max_locais_por_rota', 5)

        print(f"\n{'='*60}")
        print(f"🚀 NOVA REQUISIÇÃO: {len(locais_input)} locais")
        print(f"{'='*60}\n")

        # 1. GEOCODIFICAÇÃO
        print("📍 ETAPA 1: Geocodificação...")
        locais_processados = []

        for loc in locais_input:
            if 'lat' in loc and 'lon' in loc:
                # Já tem coordenadas
                locais_processados.append({
                    'id': loc['id'],
                    'lat': float(loc['lat']),
                    'lon': float(loc['lon']),
                    'name': loc.get('name', f"Local {loc['id']}")
                })
            elif 'endereco' in loc:
                # Precisa geocodificar
                coords = geocode_address(loc['endereco'])
                if coords:
                    locais_processados.append({
                        'id': loc['id'],
                        'lat': coords[0],
                        'lon': coords[1],
                        'name': loc.get('name', loc['endereco'][:50])
                    })
                else:
                    print(f"⚠️ Pulando local {loc['id']} (geocodificação falhou)")
            else:
                print(f"⚠️ Local {loc['id']} sem 'lat/lon' ou 'endereco'")

        if len(locais_processados) == 0:
            return jsonify({
                'success': False,
                'error': 'Nenhum local válido após geocodificação'
            }), 400

        print(f"✅ {len(locais_processados)} locais geocodificados\n")

        # 2. CLUSTERING
        print("📊 ETAPA 2: Clustering geográfico...")
        clusters = create_compact_clusters(locais_processados, max_diameter_km)

        # Dividir clusters grandes
        final_clusters = []
        for cluster in clusters:
            if len(cluster) > max_locais_por_rota:
                subclusters = split_large_cluster(cluster, locais_processados, max_locais_por_rota)
                final_clusters.extend(subclusters)
            else:
                final_clusters.append(cluster)

        print(f"✅ {len(final_clusters)} blocos finais\n")

        # 3. OTIMIZAÇÃO DE ROTAS (CVRP)
        print("🚗 ETAPA 3: Otimização de rotas (CVRP)...")

        depot_coords = (base['lat'], base['lon'])
        blocos_otimizados = []
        mapas_gerados = []

        for bloco_idx, cluster_indices in enumerate(final_clusters, 1):
            print(f"\n  📦 Bloco {bloco_idx} ({len(cluster_indices)} locais)...")

            cluster_locations = [locais_processados[i] for i in cluster_indices]

            # Resolver CVRP
            cvrp_result = solve_cvrp(depot_coords, cluster_locations, max_locais_por_rota)

            if cvrp_result:
                # Gerar mapa
                map_filename = f"rota_bloco_{bloco_idx}_{int(time.time())}.html"
                map_path = create_route_map(
                    depot=base,
                    locations=cluster_locations,
                    routes=cvrp_result['routes'],
                    filename=map_filename
                )

                blocos_otimizados.append({
                    'bloco_id': bloco_idx,
                    'num_locais': len(cluster_locations),
                    'num_rotas': cvrp_result['num_vehicles'],
                    'distancia_total_km': cvrp_result['total_distance_km'],
                    'tempo_total_min': cvrp_result['total_duration_minutes'],  # ✅ TEMPO REAL DO OSRM
                    'rotas': cvrp_result['routes'],
                    'mapa_url': f"/maps/{map_filename}"
                })

                mapas_gerados.append(map_filename)

                print(f"  ✅ {cvrp_result['num_vehicles']} rotas, {cvrp_result['total_distance_km']}km, {cvrp_result['total_duration_minutes']}min total")
            else:
                print(f"  ❌ Falha ao otimizar bloco {bloco_idx}")

        print(f"\n{'='*60}")
        print(f"✅ CONCLUÍDO: {len(blocos_otimizados)} blocos otimizados")
        print(f"{'='*60}\n")

        # Resposta
        return jsonify({
            'success': True,
            'timestamp': datetime.now().isoformat(),
            'resumo': {
                'total_locais': len(locais_processados),
                'total_blocos': len(blocos_otimizados),
                'total_rotas': sum(b['num_rotas'] for b in blocos_otimizados),
                'distancia_total_km': sum(b['distancia_total_km'] for b in blocos_otimizados)
            },
            'blocos': blocos_otimizados,
            'mapas': mapas_gerados
        }), 200

    except Exception as e:
        print(f"❌ ERRO: {e}")
        import traceback
        traceback.print_exc()

        return jsonify({
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }), 500


@app.route('/maps/<filename>', methods=['GET'])
def serve_map(filename):
    """Servir mapas HTML gerados"""
    filepath = os.path.join(MAPS_DIR, filename)
    if os.path.exists(filepath):
        return send_file(filepath)
    else:
        return jsonify({'error': 'Mapa não encontrado'}), 404


@app.route('/test-osrm', methods=['GET'])
def test_osrm():
    """Testar conectividade com OSRM local"""
    try:
        # Vitória-ES para Serra-ES
        coords = [(-20.3155, -40.3128), (-20.1284, -40.3089)]

        response = requests.get(
            f"{OSRM_URL}/route/v1/driving/{coords[0][1]},{coords[0][0]};{coords[1][1]},{coords[1][0]}",
            timeout=5
        )

        if response.status_code == 200:
            data = response.json()
            return jsonify({
                'success': True,
                'osrm_status': 'online',
                'test_route': {
                    'distance_km': data['routes'][0]['distance'] / 1000,
                    'duration_min': data['routes'][0]['duration'] / 60
                }
            })
        else:
            return jsonify({
                'success': False,
                'error': f"OSRM retornou status {response.status_code}"
            }), 500

    except Exception as e:
        return jsonify({
            'success': False,
            'error': f"OSRM offline ou inacessível: {str(e)}"
        }), 500


if __name__ == '__main__':
    print("""
    ╔════════════════════════════════════════════════════════════╗
    ║                                                            ║
    ║        🚗  API de Otimização de Rotas - FleetFlow  🚗     ║
    ║                                                            ║
    ╠════════════════════════════════════════════════════════════╣
    ║                                                            ║
    ║  Endpoints disponíveis:                                   ║
    ║  • POST /otimizar       - Otimizar rotas e blocos         ║
    ║  • GET  /health         - Status da API                   ║
    ║  • GET  /test-osrm      - Testar OSRM                     ║
    ║  • GET  /maps/<file>    - Visualizar mapas                ║
    ║                                                            ║
    ╚════════════════════════════════════════════════════════════╝
    """)

    # Produção: usar Gunicorn
    # gunicorn -w 4 -b 0.0.0.0:8000 app:app

    # Desenvolvimento
    app.run(host='0.0.0.0', port=8000, debug=True)

# ============================================================
# ENDPOINTS ASSÍNCRONOS (para evitar timeout Cloudflare)
# ============================================================

import uuid
import os

JOBS_DIR = 'jobs'
os.makedirs(JOBS_DIR, exist_ok=True)

@app.route('/otimizar-async', methods=['POST'])
def otimizar_async():
    """Iniciar otimização assíncrona"""
    try:
        data = request.get_json()
        
        # Criar job
        job_id = str(uuid.uuid4())
        job = {
            'job_id': job_id,
            'status': 'pending',
            'created_at': datetime.now().isoformat(),
            'data': data
        }
        
        job_file = f'{JOBS_DIR}/{job_id}.json'
        with open(job_file, 'w') as f:
            json.dump(job, f)
        
        return jsonify({
            'success': True,
            'job_id': job_id,
            'status': 'pending',
            'message': 'Job criado. Use /job-status/{job_id} para verificar progresso.'
        }), 202
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/job-status/<job_id>', methods=['GET'])
def job_status(job_id):
    """Verificar status de um job"""
    try:
        job_file = f'{JOBS_DIR}/{job_id}.json'
        
        if not os.path.exists(job_file):
            return jsonify({'success': False, 'error': 'Job não encontrado'}), 404
        
        with open(job_file, 'r') as f:
            job = json.load(f)
        
        response = {
            'success': True,
            'job_id': job_id,
            'status': job['status'],
            'created_at': job.get('created_at'),
            'started_at': job.get('started_at'),
            'completed_at': job.get('completed_at'),
            'failed_at': job.get('failed_at')
        }
        
        if job['status'] == 'completed':
            response['result'] = job.get('result')
        elif job['status'] == 'failed':
            response['error'] = job.get('error')
        
        return jsonify(response), 200
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500
