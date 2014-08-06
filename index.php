

<div class="content" id="content">

    <?php

    $TASK['cookie']="schedscrape.cky";
    $baseUrl = "https://duapp2.drexel.edu";

    ?>


    <?php






    include_once "libs/aCurl/aCurl.php";
    include_once "libs/php-dom-parser/php-dom-parser.php";


    $infoPoints=array();

    $sectionCount=0;


    /* GET TERM LIST */

    set_time_limit(30);
    $c1 = new aCurl($baseUrl . "/webtms_du/app");
    $c1->setCookieFile($TASK['cookie']);
    $c1->includeHeader(true);
    $c1->maxRedirects(3);
    $c1->createCurl();

    $h1 = new simple_html_dom((string)$c1);
    $termList = $h1->find('table[class=termPanel]', 0);

    foreach($termList->find('a') as $term) {

        $termHref = str_ireplace("&amp;", "&",$baseUrl . $term->href);
        $termName = $term->innertext;

        echo "<h2>" . $termName . "</h2>";

        /* GET COLLEGE LIST */
        $c2 = new aCurl($termHref);
        $c2->setCookieFile($TASK['cookie']);
        $c2->includeHeader(true);
        $c2->maxRedirects(3);
        $c2->createCurl();

        $h2 = new simple_html_dom((string)$c2);
        $collList = $h2->find('div[id=sideLeft]', 0);

        foreach($collList->find('a') as $coll) {
            $collHref = str_ireplace("&amp;", "&",$baseUrl . $coll->href);
            $collName = $coll->innertext;

            echo "<h3>" . $collName . "</h3>";

            /* GET DEPARTMENT LIST */
            $c3 = new aCurl($collHref);
            $c3->setCookieFile($TASK['cookie']);
            $c3->includeHeader(true);
            $c3->maxRedirects(3);
            $c3->createCurl();

            $h3 = new simple_html_dom((string)$c3);
            $deptList = $h3->find('table[class=collegePanel]', 0);

            foreach($deptList->find('a') as $dept) {
                $deptHref = str_ireplace("&amp;", "&",$baseUrl . $dept->href);
                $deptName = $dept->innertext;

                echo "<h4>" . $deptName . "</h4>";

                /* GET SECTION LIST */
                $c4 = new aCurl($deptHref);
                $c4->setCookieFile($TASK['cookie']);
                $c4->includeHeader(true);
                $c4->maxRedirects(3);
                $c4->createCurl();

                $h4 = new simple_html_dom((string)$c4);
                $sectList = $h4->find('table[bgcolor=#cccccc]', 0);

                foreach($sectList->find('a') as $sect) {
                    $sectHref = str_ireplace("&amp;", "&",$baseUrl . $sect->href);
                    $sectName = $sect->innertext; //CRN

                    //echo "<h5>" . $sectName . "</h5>"; //CRN

                    $sectionCount++;

                    /* GET SECTION INFORMATION */
                    $c5 = new aCurl($sectHref);
                    $c5->setCookieFile($TASK['cookie']);
                    $c5->includeHeader(true);
                    $c5->maxRedirects(3);
                    $c5->createCurl();

                    $h5 = new simple_html_dom((string)$c5);
                    $sectInfo = $h5->find('table[bgcolor=#cccccc]', 0);

                    //echo $sectInfo;

                    foreach($sectInfo->find('tr') as $sectInfoPoint) {
                        if ($sectInfoContent = $sectInfoPoint->find('td', 1)) {
                            if ($sectInfoContent->innertext != "") {
                                $sectInfoPoints[$sectInfoPoint->find('td', 0)->innertext] = $sectInfoPoint->find('td', 1)->innertext;
                                $sectInfoPoints = array_unique($sectInfoPoints);
                            }
                        }
                    }
		var_dump($sectInfoPoints);
                }
                

                echo "<p>".$sectionCount."</p>";
            } 
        }
    }

    ?>
</div>