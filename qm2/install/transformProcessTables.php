<?php 
/* SELECT p.process AS id, (

SELECT number
FROM pms_process
WHERE id = p.process
) AS number, (

SELECT `process`
FROM pms_process
WHERE id = p.process
) AS `process` , p.direction, pi.interrelation AS interrelation_id, i.description AS interrelation_name
FROM pms_processinterrelation p
LEFT JOIN pms_processinterrelation pi ON p.process = pi.process
LEFT JOIN pms_interrelation i ON pi.interrelation = i.id
WHERE i.description IS NOT NULL
ORDER BY interrelation_id
LIMIT 0 , 30 */


header("Content-Type: text/html; charset=utf-8");

function a2html($text)
{
// Diese Funktion konvertiert deutschen Text in HTML-Code.

  $text = trim($text);
  
$paragraph = strtok($text,chr(10).chr(13));
$text = '';
while ($paragraph !== false) {
    if(substr($paragraph,0,1) == '#')
      $text .= "<ul><li>".trim(substr($paragraph,1))."</li></ul>\n";
    elseif(substr($paragraph,0,2) == '()')
      $text .= "<ol><li>".trim(substr($paragraph,2))."</li></ol>\n";
    elseif(substr($paragraph,0,3) == '===')
      $text .= "<h3>".trim(substr($paragraph,3))."</h3>\n";
    //else
    //  $text .= "<p class='content'>".trim($paragraph)."</p>\n";
    $paragraph = strtok(chr(10).chr(13));
}

  //$text = ereg_replace(chr('13'),'</p><p class=content>',$text);
  //$text = ereg_replace('#','<ul><li>',$text);
  $text = @ereg_replace("</li></ul>\n<ul><li>","</li>\n    <li>",$text);
  $text = @ereg_replace("</li></ol>\n<ol><li>","</li>\n    <li>",$text);
  $text = @ereg_replace("<ol>","<ol>",$text);
  $text = @ereg_replace("<ul>","<ul>",$text);
  $text = @ereg_replace("<li>","<li>",$text);

  return $text;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
  <head>
    <title>Export der Prozesstabellen</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  </head>
  
  <body>

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$is_active = array('y' => 1, 'n' => 0);

/*
 * process MIT INHALT FÜLLEN
 */
mysql_connect('localhost', 'root', '');
mysql_select_db('qm');

mysql_set_charset('utf8') OR DIE (mysql_error());

$query = "SELECT * FROM pms_process";
$result = mysql_query($query) OR DIE (mysql_error());

$lft_count = 1;
$tree = array();
$backToUpperLvl = false;
$rgts = array();

function createNestedSet($parent_id = 0, $parent_data = null)
{    
    global $lft_count, $tree, $backToUpperLvl, $rgts;
    
    //$parent_id = ($parent_id = 0) ? 4 : $parent_id;
    
    $is_active = array('y' => 1, 'n' => 0);
    
    if($parent_data == null)
    {
        $parent_data = array('id' => 0, 'isactive' => 'y', 'process' => 'root', 'number' => '', 'type' => '');
    }
        
    $query = "SELECT id, isactive, process, number, type, reference
              FROM pms_process
              WHERE reference = '".$parent_id."'
              ORDER BY number";
    $result = mysql_query($query) OR DIE (mysql_error());
    
    $parent_data['childs'] = getNumOfChilds($parent_data['id']);
    
    if($parent_data['childs'] == 0)
    {
        $parent_data['lft'] = $lft_count;
        $parent_data['rgt'] = $lft_count + 1;
        $lft_count = $lft_count + 2;
        
    }
    else
    {
        $parent_data['lft'] = $lft_count;
        $parent_data['rgt'] = ($lft_count + ($parent_data['childs'] * 2) + 1);
        $lft_count = $lft_count + 1;
        $rgts[] = $parent_data['rgt'];
    }
    
    if($backToUpperLvl == true)
    {
        $lft_count = $lft_count + 1;
        $parent_data['lft']++;
        $parent_data['rgt']++;
        $backToUpperLvl = false;
    }
    
    if($backToUpperLvl == true && in_array($lft_count, $rgts))
    {
        echo $parent_data['process']."<br>";
    }
    
    if(@$tree[$parent_data['reference']]['rgt'] == $lft_count)
    {
        $backToUpperLvl = true;
    }
    
    
    if($parent_data['number'] == 'L2' OR $parent_data['number'] == 'L3')
    {
        $lft_count = $lft_count + 1;
        $parent_data['lft']++;
        $parent_data['rgt']++;
    }
    
    if(!isset($parent_data['reference']))
    {
        $parent_id = 'null';
    }
    elseif($parent_data['reference'] == 0)
    {
        $parent_id = 4;
    }
    else
    {
        $parent_id = $parent_data['reference'];
    }
    
        
    $tree_node = array();
    $tree_node['process_id'] = $parent_data['id'];
    $tree_node['name'] = $parent_data['process'];
    $tree_node['notation'] = $parent_data['number'];
    $tree_node['lft'] = $parent_data['lft'];
    $tree_node['rgt'] = $parent_data['rgt'];
    $tree_node['process_type_id'] = $parent_data['type'];
    $tree_node['num_childs'] = $parent_data['childs'];
    $tree_node['date_created'] = time();
    $tree_node['is_active'] = $is_active[$parent_data['isactive']];
    $tree_node['parent_id'] = $parent_id;
    
    if($tree_node['process_id'] == 0)
    {
        $tree_node['process_id'] = 4;
    }
    
    $tree[$parent_data['id']] = $tree_node;
    
    //echo "id: ".$parent_data['id']." - name: ".$tree_node['name']."<br>";
    
    if(count($result > 0))
    {
        while($row = mysql_fetch_assoc($result))
        {
            createNestedSet($row['id'], $row);
        }
    }
    
    return $tree;
}
function getNumOfChilds($parent_id, $counter = 0)
{
    $query = "SELECT COUNT(*)
              FROM pms_process 
              WHERE reference = '".$parent_id."'";
    $query = "SELECT id
              FROM pms_process
              WHERE reference = '".$parent_id."'";
    $res = mysql_query($query);
    $num_rows = mysql_num_rows($res);
    
    if($num_rows > 0)
    {
        $counter = $counter + $num_rows;
        while($row = mysql_fetch_assoc($res))
        {
            $counter = getNumOfChilds($row['id'], $counter);
        }
    }
    return $counter;
}

$tree = createNestedSet();
$values = array();

?>
<table>
  <tr>
    <td>Nr</td>
    <td>Name</td>
    <td>lft</td>
    <td>rgt</td>
  </tr>
<?php 
/*
foreach($tree AS $node)
{
    echo "<tr><td>".$node['notation']."</td><td>".$node['name']."</td><td>".$node['lft']."&nbsp;&nbsp;&nbsp;<td>".$node['rgt']."</td></tr>";
}*/
?>
</table>
<?php 
$insert_query = "
INSERT INTO process
(process_id, name, notation, lft, rgt, parent, process_type_id, date_created, is_active)
VALUES
";
$values = array();
foreach($tree AS $row)
{
    $values[] = "(".$row['process_id'].", '".$row['name']."', '".$row['notation']."', '".$row['lft']."', '".$row['rgt']."', ".$row['parent_id'].", '".$row['process_type_id']."', '".$row['date_created']."', '".$row['is_active']."')";
}
$insert_query_process = $insert_query. "" .implode(', 
', $values). ";";
echo "<h1>process</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_process;
echo "</textarea>";

/*
SELECT s.name, s.process_id, (
s.rgt - s.lft -1
) /2 AS num_childs, (
COUNT( * ) -1
) AS
LEVEL , s.lft, s.rgt
FROM PROCESS v,
PROCESS s
WHERE s.lft
BETWEEN v.lft
AND v.rgt
GROUP BY s.lft
LIMIT 0 , 30 
 */

/*
 * process_attributes MIT INHALT FÜLLEN!
 */
$query = "SELECT *
          FROM pms_attribute
          ORDER BY id";
$result = mysql_query($query) OR DIE (mysql_error());

$values = array();

$insert_query = "
INSERT INTO process_attributes
(process_attribute_id, name, position, form_type, form_size, date_created, is_active)
VALUES
";

while($row = mysql_fetch_assoc($result))
{
    $values[] = "(".$row['id'].", '".$row['attribute']."', ".$row['order'].", '".$row['form']."', ".$row['formsize'].", ".time().", ".$is_active[$row['isactive']].")";
}
$insert_query_attributes = $insert_query. "" .implode(', 
', $values).";";
echo "<h1>process_attributes</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_attributes;
echo "</textarea>";


/*
 * process_has_attribute
 */

$query = "SELECT *
          FROM pms_documentation";
$result = mysql_query($query) OR DIE (mysql_error());

$values = array();

$insert_query = "
INSERT INTO process_has_attribute
(pha_id, process_attribute_id, process_id, attribute_value, date_created, is_active)
VALUES
";

while($row = mysql_fetch_assoc($result))
{
    $row['content'] = a2html($row['content']);
    if($row['content'] != '')
    $values[] = "('', ".$row['attribute'].", ".$row['process'].", '".$row['content']."', ".time().", ".$is_active[$row['isactive']].")";
}
$insert_query_p2a = $insert_query. "" .implode(', 
', $values).";";
echo "<h1>process_has_attributes</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_p2a;
echo "</textarea>";



/*
 * process_types
 */

$query = "SELECT *
          FROM pms_processtype
          ORDER BY id";
$result = mysql_query($query) OR DIE (mysql_error());

$values = array();

$insert_query = "
INSERT INTO process_types
(process_type_id, shortcut, name, position, date_created, is_active)
VALUES
";

while($row = mysql_fetch_assoc($result))
{
    $values[] = "(".$row['id'].", '".$row['shortcut']."', '".$row['processtype']."', ".$row['order'].", ".time().", ".$is_active[$row['isactive']].")";
}
$insert_query_types = $insert_query. "" .implode(', 
', $values).";";
echo "<h1>process_types</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_types;
echo "</textarea>";

/*
 * process_interrelations
*/

$query = "SELECT id
          FROM pms_process
          ORDER BY id";
$res = mysql_query($query) OR DIE (mysql_error());
$values = array();
$codes = array();

while($p = mysql_fetch_assoc($res))
{
    $query = "SELECT pi.direction, p.id AS process_id, pi.interrelation AS process_interrelation_id, pi.isactive
            FROM pms_processinterrelation pi
            LEFT JOIN pms_interrelation i ON pi.interrelation = i.id
            LEFT JOIN pms_process p ON pi.process = p.id
            WHERE pi.process != ".$p['id']."
            AND pi.interrelation
            IN (
                SELECT i2.id
                FROM pms_interrelation i2, pms_processinterrelation pi2
                WHERE i2.id = pi2.interrelation
                AND pi2.process = ".$p['id']."
            )
            ORDER BY process_id DESC";

    $result = mysql_query($query) OR DIE (mysql_error());

    $insert_query = "INSERT INTO process_has_process
(from_process_id, to_process_id, process_interrelation_id, date_created, is_active)
VALUES";

    while($row = mysql_fetch_assoc($result))
    {
        if($row['process_id'] != '')
        {
            if($row['direction'] == 'in')
            {
                $from = $row['process_id'];
                $to = $p['id'];
            }
            elseif($row['direction'] == 'out')
            {
                $from = $p['id'];
                $to = $row['process_id'];
            }
        }
        
        //code um zu vermeiden dass die schnittstellen doppelt eingetragen werden
        $code = $from.".".$to.".".$row['process_interrelation_id'];
        if(in_array($code, $codes))
        {
            //echo "<br>".$code;
        }
        else
        {
            $codes[] = $code;
            $values[] = "(".$from.", ".$to.", ".$row['process_interrelation_id'].", ".time().", ".$is_active[$row['isactive']].")";
        }
        
    }

}

$insert_query_inter = $insert_query. "" .implode(', 
', $values).";";
echo "<h1>process_has_process</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_inter;
echo "</textarea>";


$query = "SELECT id AS process_interrelation_id, isactive, description
          FROM pms_interrelation 
          ORDER BY id";
$res = mysql_query($query) OR DIE (mysql_error());

$insert_query = "INSERT INTO process_interrelations
(process_interrelation_id, description, created_on, is_active)
VALUES";

$values = array();
while($row = mysql_fetch_assoc($res))
{
    $values[] = "(".$row['process_interrelation_id'].", '".$row['description']."', ".time().", ".$is_active[$row['isactive']].")";
}

$insert_query_rel = $insert_query. "" .implode(', 
', $values).";";
echo "<h1>process_interrelations</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_rel;
echo "</textarea>";



echo "<h1>Alle Prozesstabellen am St&uuml;ck</h1>";
echo "<textarea cols=\"140\" rows=\"10\">";
echo $insert_query_process . "\n";
echo $insert_query_attributes . "\n";
echo $insert_query_p2a . "\n";
echo $insert_query_types . "\n";
echo $insert_query_inter . "\n";
echo $insert_query_rel . "\n";
echo "</textarea>";

?>
</body>
</html>