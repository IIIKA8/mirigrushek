<?php // crud.php
require_once __DIR__ . '/config.php';

function select($query)
{
    try {
        $result = $_SERVER['db']->query($query, MYSQLI_USE_RESULT);
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $err) {
        return ['status' => 'failed', 'message' => $err->getMessage()];
    }
}

function select_prepared($query, $types, ...$params)
{
    $stmt = $_SERVER['db']->prepare($query);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function select_one_prepared($query, $types, ...$params)
{
    $rows = select_prepared($query, $types, ...$params);
    return $rows[0] ?? null;
}

function execute_prepared($query, $types, ...$params)
{
    $stmt = $_SERVER['db']->prepare($query);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function insert($query)
{
    try {
        $result = $_SERVER['db']->query($query, MYSQLI_USE_RESULT);
        return $result ? ['status' => 'success'] : ['status' => 'failed'];
    } catch (Exception $err) {
        return ['status' => 'failed', 'message' => $err->getMessage()];
    }
}

function update($query)
{
    try {
        $result = $_SERVER['db']->query($query, MYSQLI_USE_RESULT);
        return $result ? ['status' => 'success'] : ['status' => 'failed'];
    } catch (Exception $err) {
        return ['status' => 'failed', 'message' => $err->getMessage()];
    }
}

function delete($query)
{
    try {
        $result = $_SERVER['db']->query($query, MYSQLI_USE_RESULT);
        return $result ? ['status' => 'success'] : ['status' => 'failed'];
    } catch (Exception $err) {
        return ['status' => 'failed', 'message' => $err->getMessage()];
    }
}
