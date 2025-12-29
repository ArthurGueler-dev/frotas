#!/usr/bin/env python3
"""
Script para adicionar a função split_large_cluster ao app.py
"""

import sys

# Função a ser adicionada
FUNCTION_CODE = '''
def split_large_cluster(cluster_indices, locais_processados, max_size):
    """
    Divide um cluster grande em sub-clusters menores garantindo que cada um tenha no máximo max_size locais.
    Usa algoritmo guloso nearest neighbor para manter proximidade geográfica.
    """
    if len(cluster_indices) <= max_size:
        return [cluster_indices]

    print(f"  ⚠️  Cluster com {len(cluster_indices)} locais excede limite de {max_size}. Dividindo...")

    # Calcular centróide do cluster
    lats = [locais_processados[i]['lat'] for i in cluster_indices]
    lons = [locais_processados[i]['lon'] for i in cluster_indices]
    centroid_lat = sum(lats) / len(lats)
    centroid_lon = sum(lons) / len(lons)

    # Ordenar locais por distância ao centróide
    def distance_to_centroid(idx):
        loc = locais_processados[idx]
        return ((loc['lat'] - centroid_lat)**2 + (loc['lon'] - centroid_lon)**2)**0.5

    sorted_indices = sorted(cluster_indices, key=distance_to_centroid)

    # Dividir em sub-clusters de tamanho máximo
    subclusters = []
    for i in range(0, len(sorted_indices), max_size):
        subcluster = sorted_indices[i:i + max_size]
        subclusters.append(subcluster)
        print(f"    ✅ Sub-cluster criado com {len(subcluster)} locais")

    print(f"  ✅ Cluster dividido em {len(subclusters)} sub-clusters")
    return subclusters

'''

def main():
    input_file = '/root/frotas/python-api/app.py'
    output_file = '/root/frotas/python-api/app_modified.py'

    with open(input_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    # Procurar onde adicionar (antes de def solve_cvrp)
    insert_index = None
    for i, line in enumerate(lines):
        if line.strip().startswith('def solve_cvrp('):
            insert_index = i
            break

    if insert_index is None:
        print("❌ Não encontrou 'def solve_cvrp'")
        sys.exit(1)

    # Inserir função
    lines.insert(insert_index, FUNCTION_CODE)

    # Salvar arquivo modificado
    with open(output_file, 'w', encoding='utf-8') as f:
        f.writelines(lines)

    print(f"✅ Função adicionada em {output_file}")
    print(f"   Linha de inserção: {insert_index}")

if __name__ == '__main__':
    main()
