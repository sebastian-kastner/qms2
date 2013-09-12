<?php
header("Content-Type: text/html; charset=utf-8");

function MultiArraySort($multiArray, $secondIndex)
{
   while (list($firstIndex, ) = each($multiArray))
       $indexMap[$firstIndex] = $multiArray[$firstIndex][$secondIndex];
       asort($indexMap);
   while (list($firstIndex, ) = each($indexMap))
       if (is_numeric($firstIndex))
           $sortedArray[] = $multiArray[$firstIndex];
       else $sortedArray[$firstIndex] = $multiArray[$firstIndex];
   return $sortedArray;
}

$lines = file('rollen.csv');
echo "<pre>";
$roles = array();
$role_data = array();
$count = 2;
foreach($lines AS $line)
{
    $line_a = explode(',', $line);
    $line_a[1] = str_replace('"', '', $line_a[1]);
    $line_a[2] = str_replace('"', '', $line_a[2]);
    
    $active = ($line_a[2] == '') ? 0 : 1;
    $role_data[] = '(\'\', '.$line_a[0].', \''.$line_a[1].'\', '.$active.', '.$count.', '.($count+1).')'; 
    $roles[] = array(
                'lsf_role_id' => $line_a[0], 
                'role_name' => $line_a[1], 
                'active' => $active,
                'lft' => $count,
                'rgt' => $count+1
               );
    $count = $count+2;
}
$roles = MultiArraySort($roles, 'lsf_id');

$query = "INSERT INTO acl_roles_lsf
(role_id, lsf_role_id, role_name, active, lft, rgt)
VALUES
('', '', 'root', '1', 1, ".(count($role_data)+1)."),
".implode(',
', $role_data);

echo "<h1>Rollen aus dem LSF</h1>";
echo '<textarea cols="100" rows="10">';
echo $query;
echo "</textarea>";