<?php
/**
 * Script DEFINITIVO para atualizar TODOS os veículos do CSV
 * FORÇA a atualização de todos os campos, não apenas vazios
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

// Função para converter valor monetário BR para float
function converterValor($valor) {
    if (empty($valor) || $valor === 'R$-' || $valor === 'R$ -') return 0;
    // Remove "R$", espaços, pontos de milhar e converte vírgula para ponto
    $valor = str_replace(array('R$', ' ', '.'), '', $valor);
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}

// Função para extrair marca do campo "Marca/Modelo/Versão"
function extrairMarca($marcaModeloVersao) {
    $partes = explode('/', $marcaModeloVersao);
    if (count($partes) > 0) {
        $marca = trim($partes[0]);
        // Se começar com a marca conhecida
        $marcas = array('CHEVROLET', 'FIAT', 'FORD', 'HONDA', 'HYUNDAI', 'IVECO', 'M.BENZ', 'MB', 'MMC', 'MITSUBISHI', 'RENAULT', 'TOYOTA', 'VW', 'VOLKSWAGEN');
        foreach ($marcas as $m) {
            if (stripos($marca, $m) === 0) {
                return $m;
            }
        }
        return $marca;
    }
    return '';
}

// Função para extrair potência e cilindradas
function extrairPotenciaCilindradas($campo) {
    // Formato: "78CV/1000" ou "0CV/124" ou "130CV/****"
    $resultado = array('potencia' => '', 'cilindradas' => '');

    if (preg_match('/(\d+)CV/', $campo, $matches)) {
        $resultado['potencia'] = $matches[1] . 'CV';
    }

    if (preg_match('/\/(\d+)/', $campo, $matches)) {
        $resultado['cilindradas'] = $matches[1];
    }

    return $resultado;
}

// Dados extraídos do CSV (81 veículos)
$veiculosCSV = array(
    array('placa' => 'OVE4358', 'modelo' => 'CHEVROLET CLASSIC LS', 'ano' => '2013', 'renavam' => '00534304168', 'chassi' => '8AGSU19F0DR202331', 'marca_modelo' => 'CHEVROLET CLASSIC LS', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'VERDE', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '78CV/1000', 'motor' => 'NAA521841', 'fipe' => 28319.00, 'ipva' => 381.11, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 377.59),
    array('placa' => 'MTQ7J93', 'modelo' => 'CHEVROLET/CELTA 1.0L LT', 'ano' => '2012', 'renavam' => '00317894757', 'chassi' => '9BGRP48F0CG145956', 'marca_modelo' => 'CHEVROLET/CELTA 1.0L LT', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '78CV/1000', 'motor' => 'NAB217543', 'fipe' => 29586.00, 'ipva' => 440.89, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 394.48),
    array('placa' => 'PPG4B36', 'modelo' => 'CHEVROLET/MONTANA LS', 'ano' => '2015', 'renavam' => '01049079385', 'chassi' => '9BGCA8030FB213502', 'marca_modelo' => 'CHEVROLET/MONTANA LS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '99CV/1400', 'motor' => 'FA7009164', 'fipe' => 43369.00, 'ipva' => 723.66, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 578.25),
    array('placa' => 'PPW0562', 'modelo' => 'CHEVROLET/ONIX 10MT JOYE', 'ano' => '2018', 'renavam' => '01138424142', 'chassi' => '9BGKL48U0JB206228', 'marca_modelo' => 'CHEVROLET/ONIX 10MT JOYE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '80CV/1000', 'motor' => 'GFG094864', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPX2803', 'modelo' => 'CHEVROLET/ONIX 10MT JOYE', 'ano' => '2018', 'renavam' => '01127262375', 'chassi' => '9BGKL48U0JB143796', 'marca_modelo' => 'CHEVROLET/ONIX 10MT JOYE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '80CV/1000', 'motor' => 'GFG064898', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'RNQ2H45', 'modelo' => 'CHEVROLET/S10 LS DD4', 'ano' => '2022', 'renavam' => '01272621593', 'chassi' => '9BG148DK0NC420463', 'marca_modelo' => 'CHEVROLET/S10 LS DD4', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '200CV/2800', 'motor' => 'LWNF212211175', 'fipe' => 133182.00, 'ipva' => 2210.31, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1775.76),
    array('placa' => 'RNQ2H54', 'modelo' => 'CHEVROLET/S10 LS DD4', 'ano' => '2022', 'renavam' => '01272621690', 'chassi' => '9BG148DK0NC420525', 'marca_modelo' => 'CHEVROLET/S10 LS DD4', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '200CV/2800', 'motor' => 'LWNF212181247', 'fipe' => 133182.00, 'ipva' => 2210.31, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1775.76),
    array('placa' => 'RBA2F98', 'modelo' => 'FIAT/DOBLO ESSENCE 7L E', 'ano' => '2021', 'renavam' => '1227336397', 'chassi' => '9BD1196GDM1156981', 'marca_modelo' => 'FIAT/DOBLO ESSENCE 7L E', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '132CV/1747', 'motor' => '370A00113606908', 'fipe' => 69021.00, 'ipva' => 969.58, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 920.28),
    array('placa' => 'FFK7H28', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256236257', 'chassi' => '9BD341ACXMY722541', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664487517', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'FPW8F78', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256236621', 'chassi' => '9BD341ACXMY722713', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664487991', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3F38', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256380196', 'chassi' => '9BD341ACXMY723947', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664492412', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3F64', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256380498', 'chassi' => '9BD341ACXMY724194', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664492761', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3H46', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256382261', 'chassi' => '9BD341ACXMY725074', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664495215', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3H62', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256382490', 'chassi' => '9BD341ACXMY725150', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664495569', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3H69', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256382555', 'chassi' => '9BD341ACXMY725217', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664495952', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3H76', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256382628', 'chassi' => '9BD341ACXMY723342', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664490028', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO3J23', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256384671', 'chassi' => '9BD341ACXMY725357', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664495867', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RMO4A32', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2021', 'renavam' => '01256386216', 'chassi' => '9BD341ACXMY723518', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664490547', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RNA8G41', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2022', 'renavam' => '01263927200', 'chassi' => '9BD341ACXNY743546', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664542888', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'RNR0D90', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2022', 'renavam' => '01273128645', 'chassi' => '9BD341ACXNY768309', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '75CV/999', 'motor' => '552720664571879', 'fipe' => 46255.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 616.73),
    array('placa' => 'SIY6H86', 'modelo' => 'FIAT/MOBI LIKE', 'ano' => '2024', 'renavam' => '1365511933', 'chassi' => '9BD341ACZRY921914', 'marca_modelo' => 'FIAT/MOBI LIKE', 'tipo' => 'PASSAGEIRO MOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '74CV/999', 'motor' => '463532E14', 'fipe' => 56935.00, 'ipva' => 773.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 759.13),
    array('placa' => 'RTG1G68', 'modelo' => 'FIAT/STRADA ENDURANCE CD', 'ano' => '2022', 'renavam' => '01282917010', 'chassi' => '9BD281B22NYW89590', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CD', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114651740', 'fipe' => 74570.00, 'ipva' => 1194.22, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 994.27),
    array('placa' => 'RBF3B52', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1252733574', 'chassi' => '9BD281A22MYV78582', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114468337', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RBG9E05', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1264540164', 'chassi' => '9BD281A22NYW26932', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114530558', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RBG9E06', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1264540261', 'chassi' => '9BD281A22NYW26859', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114517374', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RBG9E07', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1264540369', 'chassi' => '9BD281A22NYW26844', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114520139', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMJ5D10', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1253199490', 'chassi' => '9BD281A22MYV79771', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114469823', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMJ5D13', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1253199520', 'chassi' => '9BD281A22MYV79795', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114469944', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMJ5D18', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '01253199580', 'chassi' => '9BD281A22MYV79814', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114470164', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO1G52', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256288800', 'chassi' => '9BD281A22MYV98368', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114495990', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO1G96', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256289539', 'chassi' => '9BD281A22MYV97964', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114495678', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO4A08', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256385880', 'chassi' => '9BD281A22MYV95810', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114492035', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO5I38', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256499770', 'chassi' => '9BD281A22MYV96126', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114492606', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO5J29', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256501066', 'chassi' => '9BD281A22MYV97992', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114495508', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO5J32', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256501090', 'chassi' => '9BD281A22MYV98114', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114495658', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMO5J35', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1256501139', 'chassi' => '9BD281A22MYV98582', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114493987', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RMR5H78', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2021', 'renavam' => '1258608623', 'chassi' => '9BD281A22MYW08092', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114511021', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RNZ5A49', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1279146386', 'chassi' => '9BD281A22NYW65485', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114610612', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RQS3I74', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01270794121', 'chassi' => '9BD281A22NYW46497', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114577250', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RQS7F87', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01271558049', 'chassi' => '9BD281A22NYW50354', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114590052', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RQT8J27', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01268746000', 'chassi' => '9BD281A22NYW41835', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114527876', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RQT8J28', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1268746158', 'chassi' => '9BD281A22NYW42320', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114571901', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTA8J97', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1279779397', 'chassi' => '9BD281A22NYW66087', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114622503', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTA9A37', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1279779931', 'chassi' => '9BD281A22NYW66160', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114621273', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTA9A39', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01279779974', 'chassi' => '9BD281A22NYW66175', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114621267', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTA9A40', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01279779990', 'chassi' => '9BD281A22NYW66176', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114622496', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTA9A41', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01279780000', 'chassi' => '9BD281A22NYW66220', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114621642', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTA9A55', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01279780239', 'chassi' => '9BD281A22NYW66260', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114621655', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTA9J00', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01279810936', 'chassi' => '9BD281A22NYW67225', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114623642', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTB4D56', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01280055518', 'chassi' => '9BD281A22NYW66725', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114593600', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTB5E31', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01280067613', 'chassi' => '9BD281A22NYW66730', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114594500', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTB5F87', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '1280069268', 'chassi' => '9BD281A22NYW71347', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114629564', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTB5G60', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01280070029', 'chassi' => '9BD281A22NYW72227', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114622901', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTE5D36', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01281963779', 'chassi' => '9BD281A22NYW84056', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114648105', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTG2F73', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01282934268', 'chassi' => '9BD281A22NYW89275', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '88CV/1368', 'motor' => '327A0114651732', 'fipe' => 68829.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 917.72),
    array('placa' => 'RTS9B34', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01290104848', 'chassi' => '9BD281A2DNYX01123', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1368', 'motor' => '463506274658838', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTS9B92', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01290105496', 'chassi' => '9BD281A2DNYX01293', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1368', 'motor' => '463506274657532', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTS9D53', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01290107286', 'chassi' => '9BD281A2DNYX01318', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1368', 'motor' => '463506274658926', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTS9E12', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01290107952', 'chassi' => '9BD281A2DNYX01357', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1368', 'motor' => '463506274657574', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'RTS9E91', 'modelo' => 'FIAT/STRADA ENDURANCE CS', 'ano' => '2022', 'renavam' => '01290108835', 'chassi' => '9BD281A2DNYX04587', 'marca_modelo' => 'FIAT/STRADA ENDURANCE CS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1368', 'motor' => '463506274660944', 'fipe' => 70550.00, 'ipva' => 1184.53, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 940.67),
    array('placa' => 'PPC6J12', 'modelo' => 'FIAT/STRADA WORKING', 'ano' => '2015', 'renavam' => '01029232668', 'chassi' => '9BD578141F7910155', 'marca_modelo' => 'FIAT/STRADA WORKING', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '86CV/1400', 'motor' => '310A20112294492', 'fipe' => 46919.00, 'ipva' => 747.40, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 625.59),
    array('placa' => 'FEV7J00', 'modelo' => 'FORD/CARGO 816 S', 'ano' => '2015', 'renavam' => '01050429050', 'chassi' => '9BFVEADS8FBS85199', 'marca_modelo' => 'FORD/CARGO 816 S', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '162CV/4462', 'motor' => '36514663', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPV1E52', 'modelo' => 'HONDA/CG 125I FAN', 'ano' => '2018', 'renavam' => '01143103510', 'chassi' => '9C2JC6900JR307480', 'marca_modelo' => 'HONDA/CG 125I FAN', 'tipo' => 'PASSAGEIRO MOTOCICLETA', 'cor' => 'PRETA', 'combustivel' => 'GASOLINA', 'pot_cil' => '0CV/124', 'motor' => 'JC69E0J307495', 'fipe' => 11455.00, 'ipva' => 66.33, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 152.73),
    array('placa' => 'RBE1J59', 'modelo' => 'HYUNDAI/HB20 10M SENSE', 'ano' => '2021', 'renavam' => '1247182611', 'chassi' => '9BHCN51AAMP140189', 'marca_modelo' => 'HYUNDAI/HB20 10M SENSE', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '80CV/998', 'motor' => 'F3LALU413114', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPI7E95', 'modelo' => 'HYUNDAI/HR HDB', 'ano' => '2016', 'renavam' => '01060362535', 'chassi' => '95PZBN7KPGB068876', 'marca_modelo' => 'HYUNDAI/HR HDB', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '130CV/2500', 'motor' => 'D356105D4CB', 'fipe' => 96865.00, 'ipva' => 1091.64, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1291.53),
    array('placa' => 'EKU9H22', 'modelo' => 'IVECO/DAILY 35S14HDCS', 'ano' => '2019', 'renavam' => '1197290831', 'chassi' => '93ZC35B01K8485800', 'marca_modelo' => 'IVECO/DAILY 35S14HDCS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '147CV/2998', 'motor' => 'F1CE34819P7282120', 'fipe' => 159984.00, 'ipva' => 2628.76, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2133.12),
    array('placa' => 'QRM8C24', 'modelo' => 'M.BENZ/ACCELO 1016', 'ano' => '2019', 'renavam' => '01220868385', 'chassi' => '9BM979076KB094672', 'marca_modelo' => 'M.BENZ/ACCELO 1016', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '156CV/4260', 'motor' => '924990U1235004', 'fipe' => 276668.00, 'ipva' => 1582.08, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2075.01),
    array('placa' => 'SFT4I72', 'modelo' => 'M.BENZ/ACCELO 1016 CE', 'ano' => '2023', 'renavam' => '1334921994', 'chassi' => '9BM951104PB307482', 'marca_modelo' => 'M.BENZ/ACCELO 1016 CE', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '156CV/4260', 'motor' => '924943U1420903', 'fipe' => 310125.00, 'ipva' => 2580.81, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2325.94),
    array('placa' => 'SGF3H84', 'modelo' => 'M.BENZ/ACCELO 1017', 'ano' => '2023', 'renavam' => '01381817880', 'chassi' => '9BM951104PB334345', 'marca_modelo' => 'M.BENZ/ACCELO 1017', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '163CV/4801', 'motor' => '924970U1451684', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPT7D92', 'modelo' => 'M.BENZ/ATEGO 2430', 'ano' => '2018', 'renavam' => '1144486901', 'chassi' => '9BM958166JB075231', 'marca_modelo' => 'M.BENZ/ATEGO 2430', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'PRATA', 'combustivel' => 'DIESEL', 'pot_cil' => '286CV/7200', 'motor' => '926994U1217462', 'fipe' => 370971.00, 'ipva' => 2274.28, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2782.28),
    array('placa' => 'RNH0A91', 'modelo' => 'MMC/L200 TRITON SPO GL', 'ano' => '2022', 'renavam' => '01269170055', 'chassi' => '93XLJKL1TNCM42073', 'marca_modelo' => 'MMC/L200 TRITON SPO GL', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '190CV/2442', 'motor' => '4N15BAG2425', 'fipe' => 154548.00, 'ipva' => 2512.48, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2060.64),
    array('placa' => 'RUR3I05', 'modelo' => 'MMC/L200 TRITON SPO GL', 'ano' => '2023', 'renavam' => '1315345975', 'chassi' => '93XLJKL1TPCN58837', 'marca_modelo' => 'MMC/L200 TRITON SPO GL', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '190CV/2442', 'motor' => '46135909283', 'fipe' => 158762.00, 'ipva' => 2512.48, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2116.83),
    array('placa' => 'RVS7E02', 'modelo' => 'MMC/L200 TRITON SPO GL', 'ano' => '2023', 'renavam' => '1329883915', 'chassi' => '93XLJKL1TPCN65915', 'marca_modelo' => 'MMC/L200 TRITON SPO GL', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '190CV/2442', 'motor' => '4N15BAJ6230', 'fipe' => 158762.00, 'ipva' => 2512.48, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2116.83),
    array('placa' => 'OVK0C71', 'modelo' => 'RENAULT/SANDERO STW 16HP', 'ano' => '2014', 'renavam' => '00584757433', 'chassi' => '93YBSR86KEJ760328', 'marca_modelo' => 'RENAULT/SANDERO STW 16HP', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '106CV/1598', 'motor' => 'K7MM764Q061700', 'fipe' => 39042.00, 'ipva' => 608.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 520.56),
    array('placa' => 'RNC4G56', 'modelo' => 'TOYOTA HILUX CDLOWM4FD', 'ano' => '2021', 'renavam' => '01264859551', 'chassi' => '8AJDA3CDXM1820227', 'marca_modelo' => 'TOYOTA HILUX CDLOWM4FD', 'tipo' => 'ESPECIAL CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '204CV/2755', 'motor' => '1GDG234027', 'fipe' => 158.62, 'ipva' => 2479.98, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2.11),
    array('placa' => 'SGD9B96', 'modelo' => 'VW/ 11.180', 'ano' => '2024', 'renavam' => '01376371062', 'chassi' => '9535E6TB5RR064384', 'marca_modelo' => 'VW/ 11.180', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '175CV/3800', 'motor' => '36805618', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'PPV7A55', 'modelo' => 'VW/10.160 DRC 4X2', 'ano' => '2018', 'renavam' => '1144189036', 'chassi' => '9531M62P2JR813645', 'marca_modelo' => 'VW/10.160 DRC 4X2', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '160CV/3800', 'motor' => '36574230', 'fipe' => 235533.00, 'ipva' => 1600.07, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 1766.50),
    array('placa' => 'QRM6D15', 'modelo' => 'VW/11.180 DRC 4X2', 'ano' => '2020', 'renavam' => '1219836203', 'chassi' => '9535V6TB2LR035450', 'marca_modelo' => 'VW/11.180 DRC 4X2', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '175CV/3800', 'motor' => '36667480', 'fipe' => 292859.00, 'ipva' => 2033.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 2196.44),
    array('placa' => 'MTQ3874', 'modelo' => 'VW/13.180 CNM', 'ano' => '2011', 'renavam' => '00335152376', 'chassi' => '953467239BR138114', 'marca_modelo' => 'VW/13.180 CNM', 'tipo' => 'CARGA CAMINHAO', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'pot_cil' => '180CV/4740', 'motor' => 'D1A055812', 'fipe' => 0, 'ipva' => 0, 'seguro' => 0, 'licenciamento' => 0, 'depreciacao' => 0),
    array('placa' => 'RHL3B76', 'modelo' => 'VW/GOL 1.6L MB5', 'ano' => '2022', 'renavam' => '1277392410', 'chassi' => '9BWAB45U2NT079331', 'marca_modelo' => 'VW/GOL 1.6L MB5', 'tipo' => 'PASSAGEIRO AUTOMOVEL', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '104CV/1598', 'motor' => 'CCRBS9320', 'fipe' => 57493.00, 'ipva' => 883.76, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 766.57),
    array('placa' => 'BDI3G10', 'modelo' => 'VW/NOVA SAVEIRO RB MBVS', 'ano' => '2020', 'renavam' => '01202027048', 'chassi' => '9BWKB45U5LP009364', 'marca_modelo' => 'VW/NOVA SAVEIRO RB MBVS', 'tipo' => 'CARGA CAMINHONETE', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'pot_cil' => '104CV/1598', 'motor' => 'CCRAV6469', 'fipe' => 54803.00, 'ipva' => 903.91, 'seguro' => 177.55, 'licenciamento' => 237.04, 'depreciacao' => 730.71)
);

$resultado = array(
    'atualizados' => array(),
    'inseridos' => array(),
    'erros' => array()
);

foreach ($veiculosCSV as $veiculo) {
    $placa = $veiculo['placa'];

    // Extrair marca
    $marca = extrairMarca($veiculo['marca_modelo']);

    // Extrair potência e cilindradas
    $potCil = extrairPotenciaCilindradas($veiculo['pot_cil']);

    // Verificar se o veículo existe
    $checkStmt = $pdo->prepare("SELECT Id FROM Vehicles WHERE LicensePlate = ?");
    $checkStmt->execute(array($placa));
    $existe = $checkStmt->fetch();

    if ($existe) {
        // UPDATE - atualiza TODOS os campos
        try {
            $sql = "UPDATE Vehicles SET
                VehicleName = ?,
                VehicleYear = ?,
                Renavam = ?,
                ChassisNumber = ?,
                Brand = ?,
                VehicleType = ?,
                Color = ?,
                FuelType = ?,
                EnginePower = ?,
                EngineDisplacement = ?,
                EngineNumber = ?,
                FipeValue = ?,
                IpvaCost = ?,
                InsuranceCost = ?,
                LicensingCost = ?,
                DepreciationValue = ?
                WHERE LicensePlate = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                $veiculo['modelo'],
                $veiculo['ano'],
                $veiculo['renavam'],
                $veiculo['chassi'],
                $marca,
                $veiculo['tipo'],
                $veiculo['cor'],
                $veiculo['combustivel'],
                $potCil['potencia'],
                $potCil['cilindradas'],
                $veiculo['motor'],
                $veiculo['fipe'],
                $veiculo['ipva'],
                $veiculo['seguro'],
                $veiculo['licenciamento'],
                $veiculo['depreciacao'],
                $placa
            ));

            $resultado['atualizados'][] = $placa;
        } catch (PDOException $e) {
            $resultado['erros'][] = array('placa' => $placa, 'erro' => $e->getMessage());
        }
    } else {
        // INSERT - cria novo veículo
        try {
            $sql = "INSERT INTO Vehicles (
                LicensePlate, VehicleName, VehicleYear, Renavam, ChassisNumber,
                Brand, VehicleType, Color, FuelType, EnginePower, EngineDisplacement, EngineNumber,
                FipeValue, IpvaCost, InsuranceCost, LicensingCost, DepreciationValue, IsWhitelisted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                $placa,
                $veiculo['modelo'],
                $veiculo['ano'],
                $veiculo['renavam'],
                $veiculo['chassi'],
                $marca,
                $veiculo['tipo'],
                $veiculo['cor'],
                $veiculo['combustivel'],
                $potCil['potencia'],
                $potCil['cilindradas'],
                $veiculo['motor'],
                $veiculo['fipe'],
                $veiculo['ipva'],
                $veiculo['seguro'],
                $veiculo['licenciamento'],
                $veiculo['depreciacao']
            ));

            $resultado['inseridos'][] = $placa;
        } catch (PDOException $e) {
            $resultado['erros'][] = array('placa' => $placa, 'erro' => $e->getMessage());
        }
    }
}

$resultado['success'] = count($resultado['erros']) === 0;
$resultado['resumo'] = array(
    'total_csv' => count($veiculosCSV),
    'atualizados' => count($resultado['atualizados']),
    'inseridos' => count($resultado['inseridos']),
    'erros' => count($resultado['erros'])
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
