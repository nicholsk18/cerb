<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add position to project boards columns

list($columns,) = $db->metaTable('project_board_column');

if(!array_key_exists('pos', $columns)) {
	$sql = "ALTER TABLE project_board_column ADD COLUMN pos tinyint NOT NULL DEFAULT 0, ADD INDEX (pos)";
	$db->ExecuteMaster($sql);
	
	$sql = "UPDATE project_board_column SET pos = 100";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Move project board `columns_json` to column records

list($columns,) = $db->metaTable('project_board');

if(array_key_exists('columns_json', $columns)) {
	$sql = "SELECT id, columns_json FROM project_board";
	$rows = $db->GetArrayMaster($sql);
	
	// Migrate project board column positions
	if(is_array($rows))
	foreach($rows as $row) {
		$column_ids = json_decode($row['columns_json'] ?? '[]', true) ?: [];
		
		if(!$column_ids) continue;
		
		$values = array_map(fn($i) => sprintf('(%d,%d)', $column_ids[$i], $i), array_keys($column_ids));
		
		if($values) {
			$sql = sprintf("INSERT IGNORE INTO project_board_column (id, pos) VALUES %s ON DUPLICATE KEY UPDATE pos=VALUES(pos)",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
		}
	}
	
	$sql = "ALTER TABLE project_board DROP COLUMN columns_json";
	$db->ExecuteMaster($sql);
}

return TRUE;