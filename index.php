<?php
include 'SpellCorrector.php';
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$option = isset($_REQUEST['option']) ? $_REQUEST['option'] : 'default';
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$corrected = false;


//spell checking 
if ($query) {
    $corrected = false;
    $queryArr = explode(" ", $query);
    $corrArray = array();
    for ($j = 0; $j < count($queryArr); $j++) {
        ini_set('memory_limit', -1);
        $correctWord = SpellCorrector::correct($queryArr[$j]);
        // echo $correctWord;
        echo $correctWord;
        
        echo $queryArr[$j];
        if ($correctWord != $queryArr[$j]) {

            $corrected = true;
            echo $corrected;
            array_push($corrArray, trim($correctWord));
        } else {
            array_push($corrArray, $queryArr[$j]);
        }
    }
    echo $corrected;
    if ($corrected) {
        $correctStr = "";
        for ($i = 0; $i < count($corrArray); $i++) {
            $correctStr = $correctStr . " " . $corrArray[$i];
        }

    }
}

if ($query) {
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('Apache/Solr/Service.php');

    // create a new solr service instance - host, port, and webapp
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample');

    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query);
    }

    $additionalParameters = array();
    if ($option == 'default') {
        $additionalParameters = array(
            'fl' => 'title og_url id description',

        );
    } else {
        $additionalParameters = array(
            'fl' => 'title og_url id description',
            'sort' => 'pageRankFile desc'
        );
    }

    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted  by searching (i.e. connection
    // problems or a query parsing error)
    try {
        $results = $solr->search($query, 0, $limit, $additionalParameters);
    } catch (Exception $e) {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}

?>
<html>

<head>
    <title>PHP Solr Client Example</title>
    <!--$results->__get('response')->docs[1] -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body {
            margin: auto;
        }

        form {
            width: 900px;

            margin-left: auto;
            margin-right: auto;
            text-align: center;
            margin-top: 150px;
        }

        .content {
            width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        ol li {
            list-style: none;
        }
    </style>

</head>

<body>
    <div class="container">
        <form accept-charset="utf-8" id="myform" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <h1 for="q" class="display-3 pb-3" style="color: black">Search Engine</h1>
                <div class="row">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-10">
                        <input class="form-control" id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" />
                    </div>
                    <div class="col-sm-1"></div>
                </div>
            </div>
            <?php
            if ($corrected) {
                ?>
                <div style="margin-bottom: 50px;">
                    <span>Did you mean: </span>
                    <a href="#" data-val="<?php echo $correctStr ?>" class="correctSubmit"> <?php echo $correctStr ?> </a>
                </div>
            <?php
        }
        ?>

            <div>Pick an Algorithm</div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="option" <?php if (isset($option) && $option == "default") echo "checked" ?> value="default">
                <label class="form-check-label">Default</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="option" <?php if (isset($option) && $option == "pagerank") echo "checked" ?> value="pagerank">
                <label class="form-check-label">PageRank</label>
            </div>
            <input class="btn btn-dark" style="margin-top: 30px" type="submit" value="Search">
        </form>
    </div>


    <?php
    // display results
    if ($results) {
        $total = (int)$results->response->numFound;
        $start = min(1, $total);
        $end = min($limit, $total);
        $mapFiles = array();
        $dirPath = '/Users/yifeng/Desktop/solr-7.7.3/latimes/';
        ?>
        <div class="content">Results <?php echo $start; ?> - <?php echo $end; ?> : </div>
        <ol>
            <?php
            // iterate result documents
            foreach ($results->response->docs as $doc) {
                $id = $title = $desc = $orurl = '';
                $mapLoaded = false;
                ?>
                <li>
                    <table class="table content mb-1" style="border: 2px solid black; text-align: left; margin-top: 2px;">
                        <?php
                        // iterate document fields / values
                        foreach ($doc as $field => $value) {
                            if ($field == 'id') {
                                $id = $value;
                            } else if ($field == 'title') {
                                $title = $value;
                            } else if ($field == 'description') {
                                $desc = $value;
                            } else {
                                $orurl = $value;
                            }
                        }
                        if ($desc == '') {
                            $desc = 'N/A';
                        }
                        if ($orurl == '') {
                        
                            if ($mapLoaded) {
                                $orurl = $mapFiles[$id];

                            } else {
                                $mapLoaded = true;
                                $row = 1;
                                if (($handle = fopen('/Users/yifeng/Desktop/hw5/data/URLtoHTML_latimes_news.csv', 'r')) !== false) {
                                    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                                        $num = count($data);
                                        $mapFiles[$dirPath . $data[0]] = $data[1];
                                        #echo $dirPath . $data[0];
                                        #echo "<br>";
                                        //echo $id;
                                    }
                                }
                                $orurl = $mapFiles[$id];
                                
                                //echo $mapFiles;

                            }
                        }

                        //snippet
                        $htmlContent = file_get_contents($id);
                        $phrases = explode(".", $htmlContent);
                        $snippet = "";
                        $queryTerms = "";
                        $querys = explode(" ", $query);
                        for ($i = 0; $i < count($querys); $i++) {
                            $queryTerms .= $querys[$i] . '(.*)';
                        }
                        $queryTerms = '/' . trim($queryTerms) . '/i';
                        foreach ($phrases as $phrase) {
                            $phrase = strip_tags($phrase);


                            if (preg_match("('|\"|;|`|~|\/|[|]|\|{|}|<|\%|>|:)", $phrase) > 0) {

                                continue;
                            }


                            if (preg_match($queryTerms, $phrase) >= 1) {
                                $snippet = $snippet . ' ' . strip_tags(trim($phrase)) . ' ';
                            }
                            if (strlen($snippet) > 170) {

                                break;
                            }
                        }
                        if (strlen($snippet) <= 1) {
                            $snippet = 'N/A';
                        } else {

                            for ($i = 0; $i < count($querys); $i++) {


                                $snippet = preg_replace('/' . $querys[$i] . '/i', '<strong style="font-weight:900; color:red;">' . $querys[$i] . '</strong>', $snippet);
                            }
                        }

                        //echo 'here';
                        ?>

                        <tr>
                            <th>Title: </th>
                            <td><a style="font-size: 1.5em; color:blue;" href="<?php echo $orurl; ?>"> <?php echo $title; ?></a></td>
                        </tr>
                        <tr>
                            <th>URL: </th>
                            <td><a style="color: blue; font-size:13px;" href="<?php echo $orurl; ?>"> <?php echo $orurl; ?></a></td>
                        </tr>
                        <tr>
                            <th>ID: </th>
                            <td><?php echo $id; ?></td>
                        </tr>
                        <tr>
                            <th>Description: </th>
                            <td><?php echo $desc; ?></td>
                        </tr>
                        <tr>
                            <th>Snippet: </th>
                            <td style="font-size: 14px; font-style:italic; font-weight: 100;"><?php echo '...' . $snippet . '...'; ?></td>
                        </tr>
                    </table>
                </li>
            <?php
        }
        ?>
        </ol>
    <?php
}
?>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#q").autocomplete({
                source: "autocomplete.php",
                minLength: 1,
            });
        });
        $('.correctSubmit').on('click', function(e) {
            e.preventDefault();
            console.log($(this).data('val'));
            $('#q').val($(this).data('val').toString().trim());
            
            
            $('#myform').submit();
        });
    </script>
</body>

</html>