"""
Script de Teste - Integração Completa de Quilometragem

Testa toda a cadeia:
1. Conexão com API Ituran
2. Cálculo de quilometragem
3. Salvamento via PHP API
4. Verificação no banco de dados

Uso:
    python test_mileage_integration.py
"""

import sys
import logging
from datetime import datetime, timedelta
from services.mileage_service import MileageService, test_api_connection, test_single_vehicle

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


def print_separator(title: str):
    """Imprime separador visual"""
    print("\n" + "=" * 70)
    print(f"  {title}")
    print("=" * 70 + "\n")


def test_phase_1_api_connection():
    """FASE 1: Testar conexão com API Ituran"""
    print_separator("FASE 1: Teste de Conexão com API Ituran")

    try:
        service = MileageService()

        # Testar com placa conhecida
        test_plate = 'RTS9B92'
        test_date = datetime.now()

        logger.info(f"Testando API Ituran com placa: {test_plate}")
        result = service.get_vehicle_odometer(test_plate, test_date)

        if result:
            print("✅ API Ituran está funcionando!")
            print(f"   Placa: {test_plate}")
            print(f"   Odômetro: {result['odometer']:,.2f} km")
            print(f"   Timestamp: {result['timestamp']}")
            print(f"   Result Code: {result['result_code']}")
            return True
        else:
            print("❌ API Ituran não respondeu corretamente")
            return False

    except Exception as e:
        logger.error(f"Erro no teste de conexão: {e}")
        print(f"❌ ERRO: {e}")
        return False


def test_phase_2_mileage_calculation():
    """FASE 2: Testar cálculo de quilometragem"""
    print_separator("FASE 2: Teste de Cálculo de Quilometragem")

    try:
        service = MileageService()

        # Testar cálculo para ontem (dados completos)
        test_plate = 'RTS9B92'
        test_date = datetime.now() - timedelta(days=1)

        logger.info(f"Calculando KM para {test_plate} em {test_date.strftime('%Y-%m-%d')}")
        result = service.calculate_daily_mileage(test_plate, test_date)

        if result:
            print("✅ Cálculo de quilometragem funcionou!")
            print(f"   Placa: {result['plate']}")
            print(f"   Data: {result['date']}")
            print(f"   Odômetro Inicial: {result['odometer_start']:,.2f} km")
            print(f"   Odômetro Final: {result['odometer_end']:,.2f} km")
            print(f"   KM Rodados: {result['km_driven']:,.2f} km")
            return result
        else:
            print("❌ Falha ao calcular quilometragem")
            return None

    except Exception as e:
        logger.error(f"Erro no cálculo de quilometragem: {e}")
        print(f"❌ ERRO: {e}")
        return None


def test_phase_3_save_to_database(mileage_data):
    """FASE 3: Testar salvamento no banco via PHP API"""
    print_separator("FASE 3: Teste de Salvamento no Banco de Dados")

    if not mileage_data:
        print("⚠️ Pulando teste de salvamento (sem dados para salvar)")
        return False

    try:
        service = MileageService()

        logger.info(f"Salvando dados no banco via PHP API...")
        success = service.save_mileage_to_database(mileage_data)

        if success:
            print("✅ Dados salvos no banco com sucesso!")
            print(f"   Placa: {mileage_data['plate']}")
            print(f"   Data: {mileage_data['date']}")
            print(f"   KM: {mileage_data['km_driven']:,.2f} km")
            return True
        else:
            print("❌ Falha ao salvar dados no banco")
            return False

    except Exception as e:
        logger.error(f"Erro ao salvar no banco: {e}")
        print(f"❌ ERRO: {e}")
        return False


def test_phase_4_verify_database():
    """FASE 4: Verificar se dados foram salvos corretamente"""
    print_separator("FASE 4: Verificação de Dados no Banco")

    try:
        import requests

        # Buscar dados via PHP API
        api_url = 'https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php'
        params = {
            'plate': 'RTS9B92',
            'limit': 5
        }

        logger.info("Buscando dados do banco via PHP API...")
        response = requests.get(api_url, params=params, timeout=10)
        response.raise_for_status()

        data = response.json()

        if data.get('success'):
            records = data.get('records', [])
            stats = data.get('statistics', {})

            print("✅ Dados recuperados do banco com sucesso!")
            print(f"   Total de registros: {data.get('total', 0)}")
            print(f"   Total KM: {stats.get('total_km', 0):,.2f} km")
            print(f"   Sucesso: {stats.get('success_count', 0)}")
            print(f"   Falhas: {stats.get('failed_count', 0)}")

            if records:
                print("\n   📋 Últimos registros:")
                for i, record in enumerate(records[:3], 1):
                    print(f"      {i}. {record['date']} - {record['km_driven']} km ({record['sync_status']})")

            return True
        else:
            print("❌ Erro ao buscar dados do banco")
            print(f"   Erro: {data.get('error', 'Desconhecido')}")
            return False

    except Exception as e:
        logger.error(f"Erro ao verificar banco: {e}")
        print(f"❌ ERRO: {e}")
        return False


def test_phase_5_full_sync():
    """FASE 5: Testar sincronização completa de todos os veículos"""
    print_separator("FASE 5: Teste de Sincronização Completa")

    try:
        service = MileageService()

        logger.info("Iniciando sincronização de TODOS os veículos...")
        print("⚠️ ATENÇÃO: Isso pode levar alguns minutos dependendo da quantidade de veículos.")
        print("   Processando...")

        stats = service.sync_all_vehicles()

        print("\n✅ Sincronização completa finalizada!")
        print(f"   Total de veículos: {stats['total']}")
        print(f"   Sucessos: {stats['success']}")
        print(f"   Falhas: {stats['failed']}")

        if stats['failed'] > 0:
            print(f"\n   ⚠️ Atenção: {stats['failed']} veículos falharam")

        return stats['failed'] == 0

    except Exception as e:
        logger.error(f"Erro na sincronização completa: {e}")
        print(f"❌ ERRO: {e}")
        return False


def run_all_tests(include_full_sync: bool = False):
    """Executa todos os testes em sequência"""
    print("\n")
    print("╔" + "=" * 68 + "╗")
    print("║" + " " * 15 + "TESTE DE INTEGRAÇÃO - QUILOMETRAGEM" + " " * 18 + "║")
    print("╚" + "=" * 68 + "╝")

    results = {
        'api_connection': False,
        'mileage_calculation': False,
        'save_database': False,
        'verify_database': False,
        'full_sync': None
    }

    # Fase 1: Teste de conexão
    results['api_connection'] = test_phase_1_api_connection()
    if not results['api_connection']:
        print("\n❌ Teste falhou na Fase 1. Abortando testes subsequentes.")
        return results

    # Fase 2: Cálculo de quilometragem
    mileage_data = test_phase_2_mileage_calculation()
    results['mileage_calculation'] = (mileage_data is not None)
    if not results['mileage_calculation']:
        print("\n❌ Teste falhou na Fase 2. Abortando testes subsequentes.")
        return results

    # Fase 3: Salvamento no banco
    results['save_database'] = test_phase_3_save_to_database(mileage_data)
    if not results['save_database']:
        print("\n⚠️ Teste falhou na Fase 3, mas continuando...")

    # Fase 4: Verificação no banco
    results['verify_database'] = test_phase_4_verify_database()

    # Fase 5: Sincronização completa (opcional)
    if include_full_sync:
        results['full_sync'] = test_phase_5_full_sync()
    else:
        print_separator("FASE 5: Teste de Sincronização Completa")
        print("⏭️ Pulado (use --full-sync para executar)")

    # Resumo final
    print_separator("RESUMO DOS TESTES")

    total_tests = 4
    passed_tests = sum([
        results['api_connection'],
        results['mileage_calculation'],
        results['save_database'],
        results['verify_database']
    ])

    print(f"✅ Testes Passou: {passed_tests}/{total_tests}")
    print(f"❌ Testes Falhou: {total_tests - passed_tests}/{total_tests}")

    if results['full_sync'] is not None:
        print(f"\n📊 Sincronização Completa: {'✅ Sucesso' if results['full_sync'] else '❌ Falhou'}")

    print("\n" + "=" * 70)

    if passed_tests == total_tests:
        print("🎉 TODOS OS TESTES PASSARAM! Sistema pronto para produção.")
    elif passed_tests >= 3:
        print("⚠️ Maioria dos testes passou, mas há problemas a resolver.")
    else:
        print("❌ Muitos testes falharam. Revise a configuração do sistema.")

    print("=" * 70 + "\n")

    return results


if __name__ == '__main__':
    # Verificar argumentos de linha de comando
    include_full_sync = '--full-sync' in sys.argv

    # Executar testes
    results = run_all_tests(include_full_sync=include_full_sync)

    # Exit code baseado nos resultados
    if all([results['api_connection'], results['mileage_calculation'],
            results['save_database'], results['verify_database']]):
        sys.exit(0)  # Sucesso
    else:
        sys.exit(1)  # Falha
