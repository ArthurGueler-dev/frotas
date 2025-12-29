def split_large_cluster(cluster_indices: List[int], locais_processados: List[Dict], max_size: int) -> List[List[int]]:
    """
    Divide um cluster grande em sub-clusters menores garantindo que cada um tenha no máximo max_size locais.
    Usa algoritmo guloso nearest neighbor para manter proximidade geográfica.

    Args:
        cluster_indices: Lista de índices dos locais no cluster
        locais_processados: Lista completa de todos os locais processados
        max_size: Tamanho máximo permitido por sub-cluster

    Returns:
        Lista de sub-clusters, cada um com no máximo max_size elementos
    """
    if len(cluster_indices) <= max_size:
        return [cluster_indices]

    print(f"  ⚠️  Cluster com {len(cluster_indices)} locais excede limite de {max_size}. Dividindo...")

    # Calcular centróide do cluster
    lats = [locais_processados[i]['lat'] for i in cluster_indices]
    lons = [locais_processados[i]['lon'] for i in cluster_indices]
    centroid_lat = sum(lats) / len(lats)
    centroid_lon = sum(lons) / len(lons)

    # Ordenar locais por distância ao centróide (mais próximos primeiro)
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
