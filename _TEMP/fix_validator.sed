s/array_map('strval'/array_map('intval'/g
s/sort(\$selectedIds);/sort(\$selectedIds, SORT_NUMERIC);/
s/sort(\$mappingKeys);/sort(\$mappingKeys, SORT_NUMERIC);/
