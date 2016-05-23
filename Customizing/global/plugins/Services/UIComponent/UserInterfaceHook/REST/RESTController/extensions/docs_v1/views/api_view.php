<?php ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ILIAS REST Plugin API</title>
    <link rel="stylesheet" href="<?php echo $this->data["viewURL"]; ?>css/style.css">
</head>

<body>

<?php
echo "REST Plugin API <br />";
echo "Number of entries: ".count($this->data["docs"])." <br />";
echo "<hr />";
echo "<table>";
foreach ($this->data["docs"] as $docEntry) {
    echo "<tr>";
    foreach ($docEntry as $key => $value) {
        echo "<td>".$value."</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>

</body>
</html>

