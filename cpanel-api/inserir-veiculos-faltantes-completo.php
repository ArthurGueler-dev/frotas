<?php
/**
 * Script para inserir veículos faltantes do CSV no banco de dados
 * com TODOS os campos preenchidos
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

// Dados completos dos veículos do CSV
$veiculosCSV = array(
    array('placa' => 'OXX3A48', 'modelo' => 'FIAT STRADA WORKING CD', 'ano' => '2017', 'renavam' => '01101952627', 'chassi' => '9BD27212XH7948227', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'L10081818', 'fipe' => 55099.00, 'ipva' => 916.85, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 734.65),
    array('placa' => 'OWZ2B59', 'modelo' => 'FIAT PALIO 1.0 FIRE', 'ano' => '2016', 'renavam' => '01030579478', 'chassi' => '9BD17103AG4206656', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '77CV', 'cilindradas' => '1000', 'motor' => 'A43120749', 'fipe' => 36497.00, 'ipva' => 610.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 486.63),
    array('placa' => 'OVN3I55', 'modelo' => 'FIAT STRADA WORKING CD', 'ano' => '2015', 'renavam' => '01020927461', 'chassi' => '9BD27212XF7722982', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'H10046604', 'fipe' => 45696.00, 'ipva' => 760.10, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 609.28),
    array('placa' => 'MTP9H28', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2019', 'renavam' => '01183682200', 'chassi' => '9BD341A20K1087188', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '1910037970', 'fipe' => 43082.00, 'ipva' => 715.69, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 574.43),
    array('placa' => 'MTP4B46', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2020', 'renavam' => '01212155091', 'chassi' => '9BD27512XL6011761', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'N10023251', 'fipe' => 65879.00, 'ipva' => 1094.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 878.39),
    array('placa' => 'RIH2E87', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01237252019', 'chassi' => '9BD341A20M1196574', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3550073100', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIO0G91', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2021', 'renavam' => '01246195206', 'chassi' => '9BD27512XM6101952', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'U10053193', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIN0G99', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01244879178', 'chassi' => '9BD341A28M1218893', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3080052370', 'fipe' => 55432.00, 'ipva' => 916.56, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIN3A92', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01245411621', 'chassi' => '9BD341A23M1223168', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3080058630', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIP2G20', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2021', 'renavam' => '01251109830', 'chassi' => '9BD27512XM6141149', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10012113', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIQ3J03', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01254310254', 'chassi' => '9BD341A23M1245048', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '3550089520', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIP7A81', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2021', 'renavam' => '01253693152', 'chassi' => '9BD27512XM6149813', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10043603', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIQ7G33', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2021', 'renavam' => '01255948051', 'chassi' => '9BD27512XM6157903', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'V10072541', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'RIS3E06', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01259706340', 'chassi' => '9BD341A22M1277813', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '4090016790', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RIS0H09', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2021', 'renavam' => '01258414682', 'chassi' => '9BD27512XM6165152', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => 'X10020331', 'fipe' => 73737.00, 'ipva' => 1223.62, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 983.16),
    array('placa' => 'OYM3E86', 'modelo' => 'FIAT ARGO 1.0', 'ano' => '2018', 'renavam' => '01153714892', 'chassi' => '9BD358117J9073816', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '66CV', 'cilindradas' => '999', 'motor' => 'B13211813', 'fipe' => 67182.00, 'ipva' => 1121.48, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 895.76),
    array('placa' => 'RIL9J23', 'modelo' => 'FIAT MOBI 1.0 LIKE', 'ano' => '2021', 'renavam' => '01243050000', 'chassi' => '9BD341A23M1203682', 'marca' => 'FIAT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '73CV', 'cilindradas' => '1000', 'motor' => '4090050620', 'fipe' => 55432.00, 'ipva' => 918.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 739.09),
    array('placa' => 'RJF5E97', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2022', 'renavam' => '01286413451', 'chassi' => '9BD27512XN6266688', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210003821', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF5C14', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2022', 'renavam' => '01285941010', 'chassi' => '9BD27512XN6259533', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210012441', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF4H30', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2022', 'renavam' => '01285851611', 'chassi' => '9BD27512XN6261253', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210010551', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJF5B44', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2022', 'renavam' => '01285867291', 'chassi' => '9BD27512XN6261527', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '210010781', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'RJK5J83', 'modelo' => 'FIAT STRADA 1.4 ENDURANCE', 'ano' => '2022', 'renavam' => '01296085680', 'chassi' => '9BD27512XN6310131', 'marca' => 'FIAT', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '88CV', 'cilindradas' => '1400', 'motor' => '310010881', 'fipe' => 80313.00, 'ipva' => 1333.02, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1070.84),
    array('placa' => 'OWW1D60', 'modelo' => 'FORD CARGO 816', 'ano' => '2016', 'renavam' => '01033193160', 'chassi' => '9BFYEAAD3GBA06254', 'marca' => 'FORD', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4400', 'motor' => 'BW2S010017', 'fipe' => 207609.00, 'ipva' => 3445.55, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2768.12),
    array('placa' => 'PPH5H88', 'modelo' => 'FORD RANGER XLS CD', 'ano' => '2016', 'renavam' => '01069199600', 'chassi' => '8AFAR23D2GJ322621', 'marca' => 'FORD', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '200CV', 'cilindradas' => '3200', 'motor' => 'QNWPPT14339', 'fipe' => 178652.00, 'ipva' => 2962.69, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2382.03),
    array('placa' => 'QRQ1E80', 'modelo' => 'HONDA HR-V EX CVT', 'ano' => '2018', 'renavam' => '01161277960', 'chassi' => '93HRU6870JZ409116', 'marca' => 'HONDA', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '140CV', 'cilindradas' => '1800', 'motor' => 'R18Z10220203455', 'fipe' => 96261.00, 'ipva' => 1598.26, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1283.48),
    array('placa' => 'OWD6G38', 'modelo' => 'HONDA HR-V LX CVT', 'ano' => '2016', 'renavam' => '01046135568', 'chassi' => '93HRU5650GZ206285', 'marca' => 'HONDA', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '140CV', 'cilindradas' => '1800', 'motor' => 'R18Z10220182152', 'fipe' => 75889.00, 'ipva' => 1259.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1011.85),
    array('placa' => 'OXP0A39', 'modelo' => 'HYUNDAI HR HD', 'ano' => '2017', 'renavam' => '01110012893', 'chassi' => '94XFBXTF4HK112010', 'marca' => 'HYUNDAI', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '130CV', 'cilindradas' => '2500', 'motor' => 'D4CBH651527', 'fipe' => 117523.00, 'ipva' => 1951.43, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1566.97),
    array('placa' => 'OVJ8G49', 'modelo' => 'HYUNDAI HR HD', 'ano' => '2014', 'renavam' => '00997689920', 'chassi' => '94XFBXTF8EK094628', 'marca' => 'HYUNDAI', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '130CV', 'cilindradas' => '2500', 'motor' => 'D4CBH502627', 'fipe' => 99648.00, 'ipva' => 1653.09, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1328.64),
    array('placa' => 'PPH6J26', 'modelo' => 'MITSUBISHI L200 TRITON', 'ano' => '2016', 'renavam' => '01063561740', 'chassi' => '93XMNK741GD013700', 'marca' => 'MITSUBISHI', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '178CV', 'cilindradas' => '2500', 'motor' => '4D56UCDZ1596', 'fipe' => 140628.00, 'ipva' => 2335.40, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1875.04),
    array('placa' => 'OVV8J15', 'modelo' => 'MB ACCELO 1016', 'ano' => '2014', 'renavam' => '01008282610', 'chassi' => '9BM384068FB729339', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241032891', 'fipe' => 247890.00, 'ipva' => 4115.09, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3305.20),
    array('placa' => 'RHK6I69', 'modelo' => 'MB ACCELO 1017', 'ano' => '2020', 'renavam' => '01215149031', 'chassi' => '9BM384078LB968653', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '173CV', 'cilindradas' => '4801', 'motor' => '93496230867648', 'fipe' => 348679.00, 'ipva' => 5788.71, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 4649.05),
    array('placa' => 'RGZ5E63', 'modelo' => 'MB ACCELO 1017', 'ano' => '2020', 'renavam' => '01205614151', 'chassi' => '9BM384078LB954014', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '173CV', 'cilindradas' => '4801', 'motor' => '93496230801217', 'fipe' => 329461.00, 'ipva' => 5468.05, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 4392.81),
    array('placa' => 'RIN7F33', 'modelo' => 'MB ACCELO 1016', 'ano' => '2021', 'renavam' => '01244992240', 'chassi' => '9BM384078MB972889', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241090700', 'fipe' => 274025.00, 'ipva' => 4548.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3653.67),
    array('placa' => 'RHL1F73', 'modelo' => 'MB ACCELO 1016', 'ano' => '2020', 'renavam' => '01222889010', 'chassi' => '9BM384078LB971039', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '156CV', 'cilindradas' => '4260', 'motor' => '64692241071730', 'fipe' => 274025.00, 'ipva' => 4548.49, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3653.67),
    array('placa' => 'RIO7D10', 'modelo' => 'MB ATEGO 2430', 'ano' => '2021', 'renavam' => '01247185981', 'chassi' => '9BM9600E5MB155028', 'marca' => 'MB', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '299CV', 'cilindradas' => '7200', 'motor' => '93692630175847', 'fipe' => 539234.00, 'ipva' => 8955.34, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 7189.79),
    array('placa' => 'MTP7F59', 'modelo' => 'RENAULT SANDERO STEPWAY', 'ano' => '2019', 'renavam' => '01186626950', 'chassi' => '93Y5SRKBLKJ714096', 'marca' => 'RENAULT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'potencia' => '117CV', 'cilindradas' => '1598', 'motor' => 'K4MA690U038019', 'fipe' => 51252.00, 'ipva' => 850.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 683.36),
    array('placa' => 'RIQ3B96', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01254056230', 'chassi' => 'MROFR22GXM6084809', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5182193', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3E44', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01245862391', 'chassi' => 'MROFR22G5M6072428', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5173652', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'PPH8A18', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2016', 'renavam' => '01071035451', 'chassi' => '9BRGU31V104039166', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6614954', 'fipe' => 137970.00, 'ipva' => 2291.19, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1839.60),
    array('placa' => 'MTP7C67', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2019', 'renavam' => '01185706291', 'chassi' => '9BRGU31VXKC157303', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '2494', 'motor' => '2KD7117285', 'fipe' => 143406.00, 'ipva' => 2380.46, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1912.08),
    array('placa' => 'PPH7E81', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2016', 'renavam' => '01070140860', 'chassi' => '9BRGU31V704037843', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6613916', 'fipe' => 137970.00, 'ipva' => 2291.19, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1839.60),
    array('placa' => 'PPJ2F91', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2017', 'renavam' => '01093308941', 'chassi' => 'MROFR22G0H8558917', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'potencia' => '171CV', 'cilindradas' => '2494', 'motor' => '2KD6809095', 'fipe' => 148451.00, 'ipva' => 2464.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1979.35),
    array('placa' => 'PPL8D24', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2018', 'renavam' => '01131403800', 'chassi' => 'MROFR22G0J6006067', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD0428098', 'fipe' => 163912.00, 'ipva' => 2721.42, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2185.49),
    array('placa' => 'PPL8J02', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2018', 'renavam' => '01131186741', 'chassi' => 'MROFR22G3J6003604', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD0420135', 'fipe' => 163912.00, 'ipva' => 2721.42, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2185.49),
    array('placa' => 'QRB4F19', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2019', 'renavam' => '01176741782', 'chassi' => 'MROFR22G2K6025437', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1141067', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRB4C96', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2019', 'renavam' => '01177006671', 'chassi' => 'MROFR22G7K6026143', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1143247', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRB4E10', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2019', 'renavam' => '01176870180', 'chassi' => 'MROFR22G4K6025753', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1141252', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'QRE6I70', 'modelo' => 'TOYOTA HILUX CD STD', 'ano' => '2019', 'renavam' => '01193014061', 'chassi' => 'MROFR22G0K6048127', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '177CV', 'cilindradas' => '2755', 'motor' => '2GD1332892', 'fipe' => 179867.00, 'ipva' => 2987.16, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2398.23),
    array('placa' => 'RHK2I69', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01221127110', 'chassi' => 'MROFR22G1M6064082', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5074127', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIN5J06', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01244618891', 'chassi' => 'MROFR22G6M6075117', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5151618', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIN1E61', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01244327130', 'chassi' => 'MROFR22G6M6073266', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5144119', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3A98', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01245618231', 'chassi' => 'MROFR22G7M6078068', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5176113', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO3D93', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01245658050', 'chassi' => 'MROFR22G9M6072293', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5173714', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO5G46', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01246284731', 'chassi' => 'MROFR22G8M6079785', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5178621', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIO5D21', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01246152710', 'chassi' => 'MROFR22G3M6079555', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5178605', 'fipe' => 224085.00, 'ipva' => 3720.75, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2987.80),
    array('placa' => 'RIS3B18', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01259295600', 'chassi' => 'MROFR22G4M6089997', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5225426', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RIS3G36', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2021', 'renavam' => '01259394261', 'chassi' => 'MROFR22G3M6089783', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5224922', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC2J69', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01278437301', 'chassi' => 'MROFR22G8N6101631', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5296523', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC1I22', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01278148201', 'chassi' => 'MROFR22G1N6102179', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5299186', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJC7H94', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01280403020', 'chassi' => 'MROFR22G5N6106055', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5328655', 'fipe' => 234405.00, 'ipva' => 3891.97, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3125.40),
    array('placa' => 'RJF6G45', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01287114370', 'chassi' => 'MROFR22G3N6116610', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5398003', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'RJJ1D72', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01298089900', 'chassi' => 'MROFR22G0N6133155', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5473636', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'RJJ9F46', 'modelo' => 'TOYOTA HILUX CD SRV', 'ano' => '2022', 'renavam' => '01301025050', 'chassi' => 'MROFR22G5N6135379', 'marca' => 'TOYOTA', 'tipo' => 'MISTO CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '204CV', 'cilindradas' => '2755', 'motor' => '2GD5479073', 'fipe' => 254987.00, 'ipva' => 4235.77, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3399.83),
    array('placa' => 'OWZ7E62', 'modelo' => 'VW 10.160 DELIVERY', 'ano' => '2016', 'renavam' => '01041612950', 'chassi' => '9536D82W7GR908048', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '3800', 'motor' => 'CNCBU21260', 'fipe' => 224478.00, 'ipva' => 3723.80, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2993.04),
    array('placa' => 'QRB5D93', 'modelo' => 'VW 10.160 DELIVERY', 'ano' => '2019', 'renavam' => '01177367391', 'chassi' => '9536D82WXKR014629', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '163CV', 'cilindradas' => '3800', 'motor' => 'CNCDU31847', 'fipe' => 247786.00, 'ipva' => 4112.23, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3303.81),
    array('placa' => 'OWX2J91', 'modelo' => 'VW 11.180 DELIVERY', 'ano' => '2016', 'renavam' => '01036455580', 'chassi' => '9536E82W2GR905610', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9ABA8J074887', 'fipe' => 233119.00, 'ipva' => 3865.72, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3108.25),
    array('placa' => 'MTP1C26', 'modelo' => 'VW 11.180 DELIVERY', 'ano' => '2019', 'renavam' => '01179951941', 'chassi' => '9536E82W0KR013949', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9ACA8J076813', 'fipe' => 255626.00, 'ipva' => 4243.06, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3408.35),
    array('placa' => 'RHL6H65', 'modelo' => 'VW 11.180 DELIVERY', 'ano' => '2020', 'renavam' => '01224064380', 'chassi' => '9536E82W4LR017298', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9AFA8C010814', 'fipe' => 279839.00, 'ipva' => 4644.93, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3731.19),
    array('placa' => 'RIO6G68', 'modelo' => 'VW 11.180 DELIVERY', 'ano' => '2021', 'renavam' => '01246579120', 'chassi' => '9536E82W5MR019972', 'marca' => 'VW', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'potencia' => '180CV', 'cilindradas' => '4580', 'motor' => 'E9AGA8C065541', 'fipe' => 298339.00, 'ipva' => 4952.05, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 3977.85)
);

$resultado = array(
    'inseridos' => array(),
    'ja_existem' => array(),
    'erros' => array()
);

foreach ($veiculosCSV as $veiculo) {
    $placa = $veiculo['placa'];

    // Verificar se já existe
    $checkStmt = $pdo->prepare("SELECT Id FROM Vehicles WHERE LicensePlate = ?");
    $checkStmt->execute(array($placa));

    if ($checkStmt->fetch()) {
        $resultado['ja_existem'][] = $placa;
        continue;
    }

    // Inserir veículo com todos os campos
    try {
        $sql = "INSERT INTO Vehicles (
            LicensePlate, VehicleName, VehicleYear, Renavam, ChassisNumber,
            Brand, VehicleType, Color, FuelType, EnginePower, EngineDisplacement, EngineNumber,
            FipeValue, IpvaCost, InsuranceCost, LicensingCost, DepreciationValue,
            IsWhitelisted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            $placa,
            $veiculo['modelo'],
            $veiculo['ano'],
            $veiculo['renavam'],
            $veiculo['chassi'],
            $veiculo['marca'],
            $veiculo['tipo'],
            $veiculo['cor'],
            $veiculo['combustivel'],
            $veiculo['potencia'],
            $veiculo['cilindradas'],
            $veiculo['motor'],
            $veiculo['fipe'],
            $veiculo['ipva'],
            $veiculo['seguro'],
            $veiculo['licenciamento'],
            $veiculo['depreciacao']
        ));

        $resultado['inseridos'][] = array(
            'placa' => $placa,
            'modelo' => $veiculo['modelo']
        );

    } catch (PDOException $e) {
        $resultado['erros'][] = array(
            'placa' => $placa,
            'erro' => $e->getMessage()
        );
    }
}

$resultado['success'] = count($resultado['erros']) === 0;
$resultado['resumo'] = array(
    'total_processados' => count($veiculosCSV),
    'inseridos' => count($resultado['inseridos']),
    'ja_existem' => count($resultado['ja_existem']),
    'erros' => count($resultado['erros'])
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
