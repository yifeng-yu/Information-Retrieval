<?php
//autocomplete
$term = isset($_GET["term"]) ? $_GET["term"] : false;

if ($term) {

    $queryTerm = explode(" ", $term);

    if (count($queryTerm) == 1) {
        $queryTerm[0] = strtolower($queryTerm[0]);
        $urlSolr = "http://localhost:8983/solr/myexample/suggest?q=" . $queryTerm[0];
        $responseSolr = json_decode((file_get_contents($urlSolr)), true);
        $result = array();
        for ($i = 0; $i < count($responseSolr['suggest']['suggest'][$queryTerm[0]]['suggestions']); $i++) {
            array_push($result, $responseSolr['suggest']['suggest'][$queryTerm[0]]['suggestions'][$i]['term']);
        }
        echo json_encode($result);
    } else if (count($queryTerm) > 1) {
        $result = array();
        for ($i = 0; $i < count($queryTerm); $i++) {
            $queryTerm[$i] = strtolower($queryTerm[$i]);
            $urlSolr = "http://localhost:8983/solr/myexample/suggest?q=" . strtolower($queryTerm[$i]);
            $responseSolr = json_decode((file_get_contents($urlSolr)), true);
            if (count($result) == 0) {
                for ($j = 0; $j < count($responseSolr['suggest']['suggest'][$queryTerm[$i]]['suggestions']); $j++) {
                    array_push($result, $responseSolr['suggest']['suggest'][$queryTerm[$i]]['suggestions'][$j]['term']);
                }
            } else {
                $arrLen = min(count($result), count($responseSolr['suggest']['suggest'][$queryTerm[$i]]['suggestions']));
                for ($k = 0; $k < $arrLen; $k++) {
                    $result[$k] = $result[$k] . " " . $responseSolr['suggest']['suggest'][$queryTerm[$i]]['suggestions'][$k]['term'];
                }
            }
        }
        echo json_encode($result);
    }
}
