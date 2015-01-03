

<div class="content" id="content">

    <?php

    error_reporting(E_ALL);

    $TASK['cookie']="schedscrape.cky";
    $baseUrl = "https://duapp2.drexel.edu";

	unlink("db.slite3");

	$db = new sqlite3('db.slite3');

/* The following is the DDL that builds the database.  KURTZ update DDL as needed. */
	$dbDDL = "
	CREATE TABLE colleges
(
    name TEXT NOT NULL
);
CREATE TABLE departments
(
    sym TEXT PRIMARY KEY NOT NULL,
    name TEXT NOT NULL
);


CREATE TABLE terms
(
    name TEXT NOT NULL
);



	";

	$db->exec($dbDDL);

    ?>


    <?php






    include_once "libs/aCurl/aCurl.php";
    include_once "libs/php-dom-parser/php-dom-parser.php";


    $infoPoints=array();

    $sectionCount=0;


    /* GET TERM LIST */

    set_time_limit(60);
    $c1 = new aCurl($baseUrl . "/webtms_du/app");
    $c1->setCookieFile($TASK['cookie']);
    $c1->includeHeader(true);
    $c1->maxRedirects(3);
    $c1->createCurl();

    $h1 = new simple_html_dom((string)$c1);
    $termList = $h1->find('table[class=termPanel]', 0);

    foreach($termList->find('a') as $term) {
		$termID = 0;
        $termHref = str_ireplace("&amp;", "&",$baseUrl . $term->href);
        $termName = $term->innertext;

		/* create entry in terms table */
		$q = "INSERT OR IGNORE INTO terms (name) VALUES ('$termName')";
		$db->exec($q);
		$q = "SELECT rowID FROM terms WHERE terms.name = '$termName'";
		$termID = $db->querySingle($q);

		/* create sections table for the term, if needed */
		$q = "CREATE TABLE IF NOT EXISTS 'sections-$termID' (
			crn INT NOT NULL UNIQUE PRIMARY KEY,
			collID INT,
			deptID INT,
			courseNum INT,
			sectTypeID INT,
			sectNum TEXT
		)";
		$db->exec($q);


        echo "<h2>$termName ($termID)</h2>";

        /* GET COLLEGE LIST */
        $c2 = new aCurl($termHref);
        $c2->setCookieFile($TASK['cookie']);
        $c2->includeHeader(true);
        $c2->maxRedirects(3);
        $c2->createCurl();

        $h2 = new simple_html_dom((string)$c2);
        $collList = $h2->find('div[id=sideLeft]', 0);

        foreach($collList->find('a') as $coll) {
			$collID = 0;
            $collHref = str_ireplace("&amp;", "&",$baseUrl . $coll->href);
            $collName = $coll->innertext;

			$q = "INSERT OR IGNORE INTO colleges (name) VALUES ('$collName')";
			$db->exec($q);
			$q = "SELECT rowID FROM colleges WHERE colleges.name = '$collName'";
			$collID = $db->querySingle($q);

            echo "<h3>$collName ($collID)</h3>";

            /* GET DEPARTMENT LIST */
            $c3 = new aCurl($collHref);
            $c3->setCookieFile($TASK['cookie']);
            $c3->includeHeader(true);
            $c3->maxRedirects(3);
            $c3->createCurl();

            $h3 = new simple_html_dom((string)$c3);
            $deptList = $h3->find('table[class=collegePanel]', 0);

            foreach($deptList->find('a') as $dept) {
				$deptID = 0;
                $deptHref = str_ireplace("&amp;", "&",$baseUrl . $dept->href);
				$deptSym = explode("&sp=",$deptHref);
				$deptSym = $db->escapeString(substr($deptSym[3],1));

				$deptName = $dept->innertext;
                $deptNameDB = $db->escapeString($deptName);

				$q = "INSERT OR IGNORE INTO departments ('sym','name') VALUES ('$deptSym','$deptNameDB')";
				$db->exec($q);
				$q = "SELECT rowID FROM departments WHERE departments.sym = '$deptSym'";
				$deptID = $db->querySingle($q);

                echo "<h4>$deptName ($deptSym $deptID)</h4>";
continue; // KURTZ make everything below this point a separate request

                /* GET SECTION LIST */
                $c4 = new aCurl($deptHref);
                $c4->setCookieFile($TASK['cookie']);
                $c4->includeHeader(true);
                $c4->maxRedirects(3);
                $c4->createCurl();

                $h4 = new simple_html_dom((string)$c4);
                $sectList = $h4->find('table[bgcolor=#cccccc]', 0);

				//echo $sectList;

				foreach($sectList->children() as $sect) {

					echo "\n"; //keeps the source almost-readable.

					if ($sect->class=="tableHeader") {
						continue;
					}

					if ($sect->bgcolor=="#63659C") {
						continue;
					}

					$sectTds=$sect->children();

					$sectCourseNum   = $sectTds[1]->innertext;
					$sectInstrType   = $sectTds[2]->innertext;
					//$sectInstrMeth   = $sectTds[4]->innertext;
					$sectNum 		 = $sectTds[5]->innertext;
					$sectHref		 = str_ireplace("&amp;", "&",$baseUrl . $sect->find('a',0)->href);
					$sectFullStatus  = $sect->find('p',0)->title;
					$sectTitle 		 = $sectTds[7]->innertext;


					echo "c#   $sectCourseNum <br/>\n";
					echo "type $sectInstrType <br/>\n";
					//echo "meth $sectInstrMeth <br/>\n";
					echo "s#   $sectNum <br/>\n";
					echo "href $sectHref <br/>\n";
					echo "full $sectFullStatus <br/>\n";
					echo "tit  $sectTitle <br/>\n";

					echo "<p>-----------------------------</p>\n";


					continue;

					$sectCourseNum   = $sect->first_child()->innertext;
					$sectInstrType   = $sect->find('td',2)->innertext;
					$sectInstrMeth   = $sect->find('td',3)->innertext;
					$sectNum 		 = $sect->find('td',4)->innertext;
					$sectLink		 = $sect->find('a',1);
					$sectCRN 		 = $sectLink->innertext;
					$sectFullStatus  = $sectLink->title;

					echo "#" . $sectCourseNum . "<br/ >";
					echo "#" . $sectInstrType . "<br/ >";
					echo "#" . $sectInstrMeth . "<br/ >";
					echo "#" . $sectNum . "<br/ >";
					echo "#" . $sectLink . "<br/ >";
					echo "#" . $sectCRN . "<br/ >";
					echo "#" . $sectFullStatus . "<br/ >";
				}

continue;

                foreach($sectList->find('a') as $sect) {
                    $sectHref = str_ireplace("&amp;", "&",$baseUrl . $sect->href);
                    $sectCRN = $sect->innertext; //CRN

					$q = "INSERT OR IGNORE INTO 'sections-$termID' ('crn','collID','deptID') VALUES ('$sectCRN','$collID','$deptID')";
					$db->exec($q);

                    //echo "<h5>" . $sectName . "</h5>"; //CRN

                    $sectionCount++;

                    /* GET SECTION INFORMATION */
//                    $c5 = new aCurl($sectHref);
//                    $c5->setCookieFile($TASK['cookie']);
//                    $c5->includeHeader(true);
//                    $c5->maxRedirects(3);
//                    $c5->createCurl();
//
//                    $h5 = new simple_html_dom((string)$c5);
//                    $sectInfo = $h5->find('table[bgcolor=#cccccc]', 0);
//
//                    //echo $sectInfo;
//
//                    foreach($sectInfo->find('tr') as $sectInfoPoint) {
//                        if ($sectInfoContent = $sectInfoPoint->find('td', 1)) {
//                            if ($sectInfoContent->innertext != "") {
//                                $sectInfoPoints[$sectInfoPoint->find('td', 0)->innertext] = $sectInfoPoint->find('td', 1)->innertext;
//                                $sectInfoPoints = array_unique($sectInfoPoints);
//                            }
//                        }
//                    }
//		var_dump($sectInfoPoints);
                }


                echo "<p>".$sectionCount."</p>";
            }
        }
    }

    ?>
</div>
