#!/usr/bin/env python3
"""
Script para otimizar a função make_matrix_symmetric
"""

import sys

def main():
    input_file = '/root/frotas/python-api/app.py'
    output_file = '/root/frotas/python-api/app_fixed.py'

    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Substituir função lenta por versão otimizada com NumPy
    old_function = '''def make_matrix_symmetric(matrix):
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

    return symmetric'''

    new_function = '''def make_matrix_symmetric(matrix):
    """
    Força uma matriz a ser simétrica pegando a média entre d[i][j] e d[j][i].
    Versão otimizada usando NumPy (muito mais rápida que loops).

    Args:
        matrix: Matriz numpy NxN

    Returns:
        Matriz simétrica NxN
    """
    # Usar NumPy para tornar simétrica de forma eficiente
    # (A + A.T) / 2 garante simetria
    symmetric = (matrix + matrix.T) / 2.0
    return symmetric'''

    if old_function in content:
        content = content.replace(old_function, new_function)
        print("✅ Função make_matrix_symmetric otimizada!")
    else:
        print("⚠️  Função antiga não encontrada, pode já estar otimizada")

    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"✅ Arquivo salvo em {output_file}")

if __name__ == '__main__':
    main()
