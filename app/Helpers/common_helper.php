<?php

function managementTypeToIdGet($id)
{
    $db = \Config\Database::connect();
    $builder = $db->table('management_staff');
    $record = $builder->getWhere(['id' => $id])->getRow();
    return $record->management_id;
}
