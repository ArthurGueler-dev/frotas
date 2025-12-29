#!/usr/bin/env python3
"""
Script para adicionar função que força simetria na matriz de distâncias
"""

import sys

# Função a ser adicionada
FUNCTION_CODE = '''
def make_matrix_symmetric(matrix):
    """
    Força uma matriz a ser simétrica pegando a média entre d[i][j] e d[j][i].
    Necessário porque OSRM pode retornar distâncias diferentes devido a ruas de mão única.

    Args:
        matrix: Matriz numpy NxN

    Returns:
        Matriz simétrica NxN
    """
    n = matrix.shape[0]
    symmetric = np.copy(matrix)

    for i in range(n):
        for j in range(i + 1, n):
            # Usar a média das duas direções
            avg = (matrix[i, j] + matrix[j, i]) / 2.0
            symmetric[i, j] = avg
            symmetric[j, i] = avg

    return symmetric

'''

def main():
    input_file = '/root/frotas/python-api/app.py'
    output_file = '/root/frotas/python-api/app_modified.py'

    with open(input_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    # Procurar onde adicionar (antes de get_osrm_distance_matrix_chunked)
    insert_index = None
    for i, line in enumerate(lines):
        if 'def get_osrm_distance_matrix_chunked' in line:
            insert_index = i
            break

    if insert_index is None:
        print("❌ Não encontrou 'def get_osrm_distance_matrix_chunked'")
        sys.exit(1)

    # Inserir função
    lines.insert(insert_index, FUNCTION_CODE)

    # Agora adicionar chamada para make_matrix_symmetric antes do return
    # Procurar o return dentro de get_osrm_distance_matrix_chunked
    for i in range(insert_index, min(insert_index + 200, len(lines))):
        if 'return full_matrix' in lines[i] and 'get_osrm_distance_matrix_chunked' in ''.join(lines[max(0, i-100):i]):
            # Adicionar chamada antes do return
            indent = '    '
            lines[i] = f'{indent}# Garantir simetria (OSRM pode ter diferenças por mão única)\n{indent}full_matrix = make_matrix_symmetric(full_matrix)\n{indent}' + lines[i].lstrip()
            print(f"✅ Adicionada chamada make_matrix_symmetric antes do return (linha {i})")
            break

    # Salvar arquivo modificado
    with open(output_file, 'w', encoding='utf-8') as f:
        f.writelines(lines)

    print(f"✅ Função adicionada em {output_file}")
    print(f"   Linha de inserção: {insert_index}")

if __name__ == '__main__':
    main()
