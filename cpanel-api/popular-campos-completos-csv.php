<?php
/**
 * Script para popular TODOS os campos da tabela Vehicles a partir do CSV
 * Campos: Brand, VehicleType, Color, FuelType, EngineNumber, EnginePower, EngineDisplacement
 *         Mileage, FipeValue, IpvaCost, InsuranceCost, LicensingCost, DepreciationValue
 *
 * IMPORTANTE: Apenas atualiza campos vazios/NULL, não sobrescreve dados existentes
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('success' => false, 'error' => 'Erro de conexao: ' . $e->getMessage())));
}

// Dados do CSV (extraídos manualmente)
// Colunas: ID;Renavam;PLACA;Exercício;Ano Fabricação;Ano Modelo;Marca/Modelo/Versão;Espécie/Tipo;Chassi;Cor;Combustível;Categoria;Potência/Cilindradas;Motor;...;FIPE;Tipo;Regional;IPVA;Seguro;Licenciamento;Depreciação;Rastreador;Locação

$veiculosCSV = array(
    array('placa' => 'OVE4358', 'marca' => 'CHEVROLET', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'VERDE', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '78CV', 'cilindradas' => '1000', 'motor' => 'NAA521841', 'fipe' => 28319.00, 'ipva' => 381.11, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 377.59),
    array('placa' => 'MTQ7J93', 'marca' => 'CHEVROLET', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '78CV', 'cilindradas' => '1000', 'motor' => 'NAB217543', 'fipe' => 29586.00, 'ipva' => 440.89, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 394.48),
    array('placa' => 'PPG4B36', 'marca' => 'CHEVROLET', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '99CV', 'cilindradas' => '1400', 'motor' => 'FA7009164', 'fipe' => 43369.00, 'ipva' => 723.66, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 578.25),
    array('placa' => 'PPW0562', 'marca' => 'CHEVROLET', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '80CV', 'cilindradas' => '1000', 'motor' => 'GFG094864', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPX2803', 'marca' => 'CHEVROLET', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '80CV', 'cilindradas' => '1000', 'motor' => 'GFG064898', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'RNQ2H45', 'marca' => 'CHEVROLET', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '200CV', 'cilindradas' => '2800', 'motor' => 'LWNF212211175', 'fipe' => 133182.00, 'ipva' => 2210.31, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1775.76),
    array('placa' => 'OXX3A48', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'L10081818', 'fipe' => 55099.00, 'ipva' => 916.85, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 734.65),
    array('placa' => 'OWZ2B59', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '77CV', 'cilindradas' => '1000', 'motor' => 'A43120749', 'fipe' => 36497.00, 'ipva' => 610.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 486.63),
    array('placa' => 'OVN3I55', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'H10046604', 'fipe' => 45696.00, 'ipva' => 760.10, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 609.28),
    array('placa' => 'MTP9H28', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '1910037970', 'fipe' => 43082.00, 'ipva' => 715.69, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 574.43),
    array('placa' => 'MTP4B46', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'N10023251', 'fipe' => 65879.00, 'ipva' => 1094.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 878.39),
    array('placa' => 'RIH2E87', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3550073100', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIO0G91', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'U10053193', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIN0G99', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3080052370', 'fipe' => 55432.00, 'ipva' => 916.56, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'MTQ3874', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3080082860', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIN3A92', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3080058630', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIP2G20', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10012113', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIQ3J03', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3550089520', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIP7A81', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10043603', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIQ7G33', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10072541', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIS3E06', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '4090016790', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIS0H09', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'X10020331', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'OYM3E86', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '66CV', 'cilindradas' => '999', 'motor' => 'B13211813', 'fipe' => 67182.00, 'ipva' => 1121.48, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 895.76),
    array('placa' => 'RIL9J23', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '4090050620', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RJF5E97', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210003821', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF5C14', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210012441', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF4H30', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210010551', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF5B44', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210010781', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJK5J83', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '310010881', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'OWW1D60', 'marca' => 'FORD', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4400', 'motor' => 'BW2S010017', 'fipe' => 207609.00, 'ipva' => 3445.55, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2768.12),
    array('placa' => 'PPH5H88', 'marca' => 'FORD', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '200CV', 'cilindradas' => '3200', 'motor' => 'QNWPPT14339', 'fipe' => 178652.00, 'ipva' => 2962.69, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2382.03),
    array('placa' => 'QRQ1E80', 'marca' => 'HONDA', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '140CV', 'cilindradas' => '1800', 'motor' => 'R18Z10220203455', 'fipe' => 96261.00, 'ipva' => 1598.26, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1283.48),
    array('placa' => 'OWD6G38', 'marca' => 'HONDA', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '140CV', 'cilindradas' => '1800', 'motor' => 'R18Z10220182152', 'fipe' => 75889.00, 'ipva' => 1259.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1011.85),
    array('placa' => 'PPV1E52', 'marca' => 'HYUNDAI', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '80CV', 'cilindradas' => '1000', 'motor' => 'G3LAEH227009', 'fipe' => 51759.00, 'ipva' => 859.19, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 690.12),
    array('placa' => 'OXP0A39', 'marca' => 'HYUNDAI', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '130CV', 'cilindradas' => '2500', 'motor' => 'D4CBH651527', 'fipe' => 117523.00, 'ipva' => 1951.43, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1566.97),
    array('placa' => 'OVJ8G49', 'marca' => 'HYUNDAI', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '130CV', 'cilindradas' => '2500', 'motor' => 'D4CBH502627', 'fipe' => 99648.00, 'ipva' => 1653.09, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1328.64),
    array('placa' => 'RHL3B76', 'marca' => 'HYUNDAI', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '130CV', 'cilindradas' => '2500', 'motor' => 'D4CBJ158082', 'fipe' => 161193.00, 'ipva' => 2674.80, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2149.24),
    array('placa' => 'PPH6J26', 'marca' => 'MITSUBISHI', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '178CV', 'cilindradas' => '2500', 'motor' => '4D56UCDZ1596', 'fipe' => 140628.00, 'ipva' => 2335.40, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1875.04),
    array('placa' => 'OVV8J15', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241032891', 'fipe' => 247890.00, 'ipva' => 4115.09, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3305.20),
    array('placa' => 'RHK6I69', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '173CV', 'cilindradas' => '4801', 'motor' => '93496230867648', 'fipe' => 348679.00, 'ipva' => 5788.71, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 4649.05),
    array('placa' => 'RGZ5E63', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '173CV', 'cilindradas' => '4801', 'motor' => '93496230801217', 'fipe' => 329461.00, 'ipva' => 5468.05, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 4392.81),
    array('placa' => 'RIN7F33', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241090700', 'fipe' => 274025.00, 'ipva' => 4548.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3653.67),
    array('placa' => 'RHL1F73', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241071730', 'fipe' => 274025.00, 'ipva' => 4548.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3653.67),
    array('placa' => 'RIO7D10', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '299CV', 'cilindradas' => '7200', 'motor' => '93692630175847', 'fipe' => 539234.00, 'ipva' => 8955.34, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 7189.79),
    array('placa' => 'MTP7F59', 'marca' => 'RENAULT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '117CV', 'cilindradas' => '1598', 'motor' => 'K4MA690U038019', 'fipe' => 51252.00, 'ipva' => 850.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 683.36),
    array('placa' => 'RIQ3B96', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5182193', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3E44', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5173652', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'PPH8A18', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6614954', 'fipe' => 137970.00, 'ipva' => 2291.19, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1839.60),
    array('placa' => 'MTP7C67', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '2494', 'motor' => '2KD7117285', 'fipe' => 143406.00, 'ipva' => 2380.46, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1912.08),
    array('placa' => 'PPH7E81', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6613916', 'fipe' => 137970.00, 'ipva' => 2291.19, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1839.60),
    array('placa' => 'PPJ2F91', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6809095', 'fipe' => 148451.00, 'ipva' => 2464.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1979.35),
    array('placa' => 'PPL8D24', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD0428098', 'fipe' => 163912.00, 'ipva' => 2721.42, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2185.49),
    array('placa' => 'PPL8J02', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD0420135', 'fipe' => 163912.00, 'ipva' => 2721.42, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2185.49),
    array('placa' => 'QRB4F19', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1141067', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRB4C96', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1143247', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRB4E10', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1141252', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRE6I70', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1332892', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'RHK2I69', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5074127', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIN5J06', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5151618', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIN1E61', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5144119', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3A98', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5176113', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3D93', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5173714', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO5G46', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5178621', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO5D21', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5178605', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIS3B18', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5225426', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RIS3G36', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5224922', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC2J69', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5296523', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC1I22', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5299186', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC7H94', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5328655', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJF6G45', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5398003', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'RJJ1D72', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5473636', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'RJJ9F46', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5479073', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'OWZ7E62', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '3800', 'motor' => 'CNCBU21260', 'fipe' => 224478.00, 'ipva' => 3723.80, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2993.04),
    array('placa' => 'QRB5D93', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '3800', 'motor' => 'CNCDU31847', 'fipe' => 247786.00, 'ipva' => 4112.23, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3303.81),
    array('placa' => 'OWX2J91', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9ABA8J074887', 'fipe' => 233119.00, 'ipva' => 3865.72, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3108.25),
    array('placa' => 'MTP1C26', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9ACA8J076813', 'fipe' => 255626.00, 'ipva' => 4243.06, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3408.35),
    array('placa' => 'RHL6H65', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9AFA8C010814', 'fipe' => 279839.00, 'ipva' => 4644.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3731.19),
    array('placa' => 'RIO6G68', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9AGA8C065541', 'fipe' => 298339.00, 'ipva' => 4952.05, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3977.85)
);

// Verificar se campo está vazio
function campoVazio($valor) {
    return $valor === null || $valor === '' || $valor === 'NULL';
}

$resultado = array(
    'atualizados' => array(),
    'nao_encontrados' => array(),
    'ja_preenchidos' => array(),
    'erros' => array()
);

foreach ($veiculosCSV as $veiculo) {
    $placa = $veiculo['placa'];

    // Verificar se o veículo existe
    $checkStmt = $pdo->prepare("SELECT * FROM Vehicles WHERE LicensePlate = ?");
    $checkStmt->execute(array($placa));
    $veiculoDB = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$veiculoDB) {
        $resultado['nao_encontrados'][] = $placa;
        continue;
    }

    // Preparar campos para atualização (apenas se vazios)
    $updates = array();
    $params = array();

    // Brand
    if (campoVazio($veiculoDB['Brand']) && !empty($veiculo['marca'])) {
        $updates[] = "Brand = ?";
        $params[] = $veiculo['marca'];
    }

    // VehicleType
    if (campoVazio($veiculoDB['VehicleType']) && !empty($veiculo['tipo'])) {
        $updates[] = "VehicleType = ?";
        $params[] = $veiculo['tipo'];
    }

    // Color
    if (campoVazio($veiculoDB['Color']) && !empty($veiculo['cor'])) {
        $updates[] = "Color = ?";
        $params[] = $veiculo['cor'];
    }

    // FuelType
    if (campoVazio($veiculoDB['FuelType']) && !empty($veiculo['combustivel'])) {
        $updates[] = "FuelType = ?";
        $params[] = $veiculo['combustivel'];
    }

    // EnginePower
    if (campoVazio($veiculoDB['EnginePower']) && !empty($veiculo['potencia'])) {
        $updates[] = "EnginePower = ?";
        $params[] = $veiculo['potencia'];
    }

    // EngineDisplacement
    if (campoVazio($veiculoDB['EngineDisplacement']) && !empty($veiculo['cilindradas'])) {
        $updates[] = "EngineDisplacement = ?";
        $params[] = $veiculo['cilindradas'];
    }

    // EngineNumber
    if (campoVazio($veiculoDB['EngineNumber']) && !empty($veiculo['motor'])) {
        $updates[] = "EngineNumber = ?";
        $params[] = $veiculo['motor'];
    }

    // FipeValue
    if ((campoVazio($veiculoDB['FipeValue']) || $veiculoDB['FipeValue'] == 0) && $veiculo['fipe'] > 0) {
        $updates[] = "FipeValue = ?";
        $params[] = $veiculo['fipe'];
    }

    // IpvaCost
    if ((campoVazio($veiculoDB['IpvaCost']) || $veiculoDB['IpvaCost'] == 0) && $veiculo['ipva'] > 0) {
        $updates[] = "IpvaCost = ?";
        $params[] = $veiculo['ipva'];
    }

    // InsuranceCost
    if ((campoVazio($veiculoDB['InsuranceCost']) || $veiculoDB['InsuranceCost'] == 0) && $veiculo['seguro'] > 0) {
        $updates[] = "InsuranceCost = ?";
        $params[] = $veiculo['seguro'];
    }

    // LicensingCost
    if ((campoVazio($veiculoDB['LicensingCost']) || $veiculoDB['LicensingCost'] == 0) && $veiculo['licenciamento'] > 0) {
        $updates[] = "LicensingCost = ?";
        $params[] = $veiculo['licenciamento'];
    }

    // DepreciationValue
    if ((campoVazio($veiculoDB['DepreciationValue']) || $veiculoDB['DepreciationValue'] == 0) && $veiculo['depreciacao'] > 0) {
        $updates[] = "DepreciationValue = ?";
        $params[] = $veiculo['depreciacao'];
    }

    if (count($updates) > 0) {
        try {
            $params[] = $placa;
            $sql = "UPDATE Vehicles SET " . implode(", ", $updates) . " WHERE LicensePlate = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $resultado['atualizados'][] = array(
                'placa' => $placa,
                'campos' => count($updates)
            );
        } catch (PDOException $e) {
            $resultado['erros'][] = array(
                'placa' => $placa,
                'erro' => $e->getMessage()
            );
        }
    } else {
        $resultado['ja_preenchidos'][] = $placa;
    }
}

$resultado['success'] = count($resultado['erros']) === 0;
$resultado['resumo'] = array(
    'total_processados' => count($veiculosCSV),
    'atualizados' => count($resultado['atualizados']),
    'nao_encontrados' => count($resultado['nao_encontrados']),
    'ja_preenchidos' => count($resultado['ja_preenchidos']),
    'erros' => count($resultado['erros'])
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
