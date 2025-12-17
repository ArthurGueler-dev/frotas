"""
Script de teste da API de Otimização de Rotas
"""

import requests
import json
import time

# Configuração
API_URL = "http://localhost:8000"

# Dados de teste
BASE = {
    "lat": -20.21155,
    "lon": -40.25223,
    "name": "Base i9 Engenharia - Serra/ES"
}

# Locais de teste (Região Metropolitana de Vitória)
LOCAIS = [
    {"id": 1, "endereco": "Shopping Vila Velha, Vila Velha-ES"},
    {"id": 2, "endereco": "Shopping Vitória, Vitória-ES"},
    {"id": 3, "endereco": "UFES, Goiabeiras, Vitória-ES"},
    {"id": 4, "endereco": "Praia da Costa, Vila Velha-ES"},
    {"id": 5, "endereco": "Centro de Vitória, Vitória-ES"},
    {"id": 6, "endereco": "Laranjeiras, Serra-ES"},
    {"id": 7, "endereco": "Jardim Camburi, Vitória-ES"},
    {"id": 8, "endereco": "Praia de Itaparica, Vila Velha-ES"},
    {"id": 9, "endereco": "Feu Rosa, Serra-ES"},
    {"id": 10, "endereco": "Jardim Limoeiro, Serra-ES"},
]


def test_health_check():
    """Testar health check"""
    print("🔍 Testando health check...")
    response = requests.get(f"{API_URL}/health")

    if response.status_code == 200:
        data = response.json()
        print(f"✅ API está online (versão {data['version']})")
        return True
    else:
        print(f"❌ API offline (status {response.status_code})")
        return False


def test_osrm():
    """Testar OSRM"""
    print("\n🔍 Testando OSRM...")
    response = requests.get(f"{API_URL}/test-osrm")

    if response.status_code == 200:
        data = response.json()
        if data['success']:
            print(f"✅ OSRM está online")
            print(f"   Teste: {data['test_route']['distance_km']:.2f}km, "
                  f"{data['test_route']['duration_min']:.1f} min")
            return True
        else:
            print(f"❌ OSRM offline: {data.get('error')}")
            return False
    else:
        print(f"❌ Erro ao testar OSRM (status {response.status_code})")
        return False


def test_otimizar():
    """Testar otimização de rotas"""
    print("\n🚀 Testando otimização de rotas...")
    print(f"   Base: {BASE['name']}")
    print(f"   Locais: {len(LOCAIS)}")

    payload = {
        "base": BASE,
        "locais": LOCAIS,
        "max_diameter_km": 5.0,
        "max_locais_por_rota": 5
    }

    print("\n📡 Enviando requisição...")
    start_time = time.time()

    response = requests.post(
        f"{API_URL}/otimizar",
        json=payload,
        timeout=300  # 5 minutos
    )

    elapsed = time.time() - start_time

    if response.status_code == 200:
        data = response.json()

        if data['success']:
            print(f"\n✅ Otimização concluída em {elapsed:.1f} segundos\n")

            # Resumo
            resumo = data['resumo']
            print("📊 RESUMO:")
            print(f"   • Locais processados: {resumo['total_locais']}")
            print(f"   • Blocos criados: {resumo['total_blocos']}")
            print(f"   • Rotas geradas: {resumo['total_rotas']}")
            print(f"   • Distância total: {resumo['distancia_total_km']} km")

            # Detalhes dos blocos
            print(f"\n📦 BLOCOS:\n")
            for bloco in data['blocos']:
                print(f"   Bloco {bloco['bloco_id']}:")
                print(f"      • {bloco['num_locais']} locais")
                print(f"      • {bloco['num_rotas']} rotas")
                print(f"      • {bloco['distancia_total_km']} km")
                print(f"      • Mapa: {API_URL}{bloco['mapa_url']}\n")

            # Salvar resultado em arquivo
            with open('resultado_teste.json', 'w', encoding='utf-8') as f:
                json.dump(data, f, indent=2, ensure_ascii=False)

            print("💾 Resultado salvo em: resultado_teste.json")

            # Abrir primeiro mapa no navegador
            if data['blocos']:
                primeiro_mapa = f"{API_URL}{data['blocos'][0]['mapa_url']}"
                print(f"\n🗺️  Abra o mapa no navegador:")
                print(f"   {primeiro_mapa}")

            return True
        else:
            print(f"❌ Erro: {data.get('error')}")
            return False
    else:
        print(f"❌ Erro HTTP {response.status_code}")
        try:
            error_data = response.json()
            print(f"   Detalhes: {error_data.get('error')}")
        except:
            print(f"   Response: {response.text[:200]}")
        return False


def test_com_coordenadas():
    """Testar com coordenadas já definidas (sem geocodificação)"""
    print("\n🚀 Testando com coordenadas pré-definidas...")

    locais_com_coords = [
        {"id": 1, "lat": -20.2974, "lon": -40.3095, "name": "Shopping Vila Velha"},
        {"id": 2, "lat": -20.3155, "lon": -40.3128, "name": "Centro Vitória"},
        {"id": 3, "lat": -20.2786, "lon": -40.3033, "name": "UFES"},
        {"id": 4, "lat": -20.3344, "lon": -40.2925, "name": "Praia da Costa"},
        {"id": 5, "lat": -20.1284, "lon": -40.3089, "name": "Laranjeiras"},
    ]

    payload = {
        "base": BASE,
        "locais": locais_com_coords,
        "max_diameter_km": 10.0,  # Diâmetro maior para testar
        "max_locais_por_rota": 3
    }

    start_time = time.time()
    response = requests.post(f"{API_URL}/otimizar", json=payload, timeout=120)
    elapsed = time.time() - start_time

    if response.status_code == 200 and response.json()['success']:
        print(f"✅ Concluído em {elapsed:.1f}s (sem geocodificação)")
        return True
    else:
        print(f"❌ Falhou")
        return False


def main():
    """Executar todos os testes"""
    print("═" * 60)
    print("   🧪 TESTES DA API DE OTIMIZAÇÃO DE ROTAS")
    print("═" * 60)

    results = {
        "Health Check": test_health_check(),
        "OSRM": test_osrm(),
        "Otimização com Geocodificação": test_otimizar(),
        "Otimização com Coordenadas": test_com_coordenadas(),
    }

    print("\n" + "═" * 60)
    print("   📊 RESULTADO DOS TESTES")
    print("═" * 60)

    for test_name, passed in results.items():
        status = "✅ PASSOU" if passed else "❌ FALHOU"
        print(f"{status:12} - {test_name}")

    total = len(results)
    passed = sum(results.values())

    print(f"\n{passed}/{total} testes passaram")
    print("═" * 60)


if __name__ == "__main__":
    main()
