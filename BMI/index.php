<!DOCTYPE html>
<html>
<head>
    <title>BMI Calculator</title>
    <link rel="stylesheet" href="stijlen\stijl.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div id="site">
<div id="inputForm">

<h1>BMI Berekenen!</h1>

<!-- HTML form -->
<form method="post">

<p>Voornaam:</p>
<input type="text" name="naam" placeholder="Vul je naam in"><br>

<p>E-mail:</p>
<input type="email" name="email" placeholder="Vul je email in"><br>

<p>Gewicht in kg:</p>
<input name="gewicht" placeholder="Vul je gewicht in"><br>

<p>Lengte in meters:</p>
<input name="lengte" placeholder="Vul je lengte in"><br><br>

<label>Geslacht:</label>
<select name="geslacht">
    <option value="1">Man</option>
    <option value="2">Vrouw</option>
    <option value="3">overig</option>
</select><br><br>

<!-- Knoppen voor het form te versturen of te resetten -->
<input type="submit" value="BMI berekenen" name="doorgaanKnop" class="knop">
<input type="submit" value="Reset" name="reset" class="knop">
</form>

<br>

<?php
    //haalt gegevens uit database
    require "./includes/config.inc.php";
    $query = "select advies from bmi;";
    $resultaat = mysqli_query($link, $query);
    $allebmi = array();
    while($rij = mysqli_fetch_array($resultaat, MYSQLI_ASSOC))
    {
        $allebmi[] = $rij;
    }

    //vertaald de php naar javascript
    $jsonbmi = json_encode($allebmi);
    echo "<script>var bmi = $jsonbmi;</script>";

    function BMIberekenen() {
        //haalt gewicht en lengte op
        $iGewicht = str_replace(" ","",str_replace(",", ".", $_POST["gewicht"]));
        $iLengte = str_replace(" ","",str_replace(",", ".", $_POST["lengte"]));

        //haalt naam, email en geslacht op
        $sNaam = ucfirst(strtolower(trim($_POST["naam"])));
        $sEmail = str_replace(" ","",strtolower($_POST["email"]));
        $iGeslacht = $_POST["geslacht"];

        //checkt of alle ingevulde informatie geldig is
        if (ctype_alpha(str_replace(" ","",$sNaam)) == false) {
            echo "Je naam is ongeldig!";
        } 
        elseif (str_contains($sEmail, "@" ) == false || str_contains($sEmail, "." ) == false) {
            echo "Je email is ongeldig!";
        }  
        elseif (ctype_digit($iGewicht) == false) {
            echo "Je hebt geen geldig gewicht ingevuld!";
        } 
        elseif (ctype_digit(str_replace(".","",$iLengte)) == false) {
            echo "Je hebt geen geldige lengte ingevuld!";
        } 
        elseif (ctype_digit(str_replace(".","",$iGewicht)) == false && ctype_digit(str_replace(".","",$iLengte)) == false) {
            echo "Je hebt geen cijfers ingevuld!";
        }
        else {
            //checkt of de ingevulde waarden realistisch zijn
            if ($iGewicht >= 300 || $iGewicht <= 0) {
                echo "Je gewicht is ongeldig!";
            } else if ($iLengte >= 2.50 || $iLengte <= 0) {
                echo "je lengte is ongeldig!";
            } else {
                //berekent bmi
                $iBmi = round($iGewicht/($iLengte*$iLengte), 1);

                //print resulaten
                echo "Hallo " . $sNaam . ",<br>";

                if ($iBmi < 18.5) {
                    echo "Je BMI is " . $iBmi . ". <br>Dit is te licht.";
                    $advies = "teLicht";
                } elseif ($iBmi < 25) {
                    echo "Je BMI is " . $iBmi . ", Dit is prima.";
                    $advies = "prima";
                } elseif ($iBmi < 30) {
                    echo "Je BMI is " . $iBmi . ". <br>Dit is te zwaar.";
                    $advies = "teZwaar";
                } else {
                    echo "Je BMI is " . $iBmi . ". <br>Dit is veel te zwaar.";
                    $advies = "veelTeZwaar";
                }

                echo "<br>We sturen dit naar je e-mail. (" . $sEmail . ")<br><br>";

                //stuurt de gegevens naar de database
                $sEmail = addslashes($sEmail);
                require "./includes/config.inc.php";
                $sql = "INSERT INTO `bmi` (`naam`, `email`, `gewicht`, `lengte`, `geslacht`, `bmi`, `advies`) VALUES ('$sNaam', '$sEmail', '$iGewicht', '$iLengte', '$iGeslacht', '$iBmi', '$advies');";
                mysqli_query($link, $sql);

                //haalt gegevens uit database*
                $query = "select advies from bmi;";
                $resultaat = mysqli_query($link, $query);
                $allebmi = array();
                while($rij = mysqli_fetch_array($resultaat, MYSQLI_ASSOC))
                {
                    $allebmi[] = $rij;
                }

                //vertaald de php naar javascript
                $jsonbmi = json_encode($allebmi);
                echo "<script>var bmi = $jsonbmi;</script>";
            }
        }
    }

    //functie die de pagina refreshed
    function ResetPagina() {
        header("Refresh:0");
    }

    //bereken bmi nadat knop is ingedrukt
    if (array_key_exists("doorgaanKnop", $_POST)) { 
        BMIberekenen();
    }

    //reset pagina nadar reset knop is ingedrukt
    if (array_key_exists("reset", $_POST)) { 
        ResetPagina();
    }
?>
</div>

<div id="grafiek">
    <p id="chartTitle"></p>
    <canvas id="chart"></canvas>
</div>

<script>
    //Pie chart
    var adviesLijst = bmi.map(function(item) {return item.advies;});
    const count = {};

    document.getElementById("chartTitle").innerHTML = bmi.length + " BMI's verdeeld naar BMI klasse!"; 
 
    for (let i = 0; i < adviesLijst.length; i++) {
        let ele = adviesLijst[i];
        if (count[ele]) {
            count[ele] += 1;
        } else {
            count[ele] = 1;
        }
    }

    var chrt = document.getElementById("chart");
        var chartId = new Chart(chrt, {
        type: "pie",
        data: {
            labels: ["Te licht", "Prima", "Te zwaar", "Veel te zwaar"],
            datasets: [{
               label: "Aantal",
               data: [count.teLicht, count.prima, count.teZwaar, count.veelTeZwaar],
               hoverOffset: 7
            }],
        },
      });
</script>
</div>
<footer>
    <?php echo "BMI calculator " . date("Y"); ?>
</footer>
</body>
</html> 
