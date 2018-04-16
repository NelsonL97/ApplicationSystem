<?php
    require_once "support.php";
    require_once "dblogin.php"; 

    //retrieving fields from form session
    session_start();
    $course = $_SESSION["course"];
    $sortby = $_SESSION["sortby"];
    $undergraduate = $_SESSION["undergraduate"];
    $graduate = $_SESSION["graduate"];

    $head = <<<HEADHEAD
    <br><img src="umdLogo.gif" alt="UMD logo"/><br>
    <hr style="height:1px;border:none;color:#333;background-color:#333;" />
HEADHEAD;

    //Building the heads of the table
    $applying_table_head =  ['First', 'Last', 'Email', 'Directory_ID', 'GPA', 'Degree', 'Experience',"Transcript","Extra Information"];
    $accepted_table_head = ['First', 'Last', 'Email', 'Directory_ID', 'GPA', 'Degree', 'Experience',"Transcript","Extra Information"];

    $applying_table = <<<THEAD
    <form action="addTA.php" method="post" class="container-fluid">
    <div>
        <h1>Applications for {$course}</h1>
    </div>
    <table class="table table-striped">
        <tr>
THEAD;

    $accepted_table = <<<TABLE2
    <form action="removeTA.php" method="post" class="container-fluid">
    <div>
        <h1>Current TAs for {$course}</h1>
    </div>
    <table class="table table-striped">
        <tr>
TABLE2;

    foreach($applying_table_head as $key=>$value) {
        $applying_table .= "<th>$value</th>";
    }

    foreach($accepted_table_head as $key=>$value) {
        $accepted_table .= "<th>$value</th>";
    }

    $applying_table .= "</tr>";
    $accepted_table .= "</tr>";


    // connecting to database;
    $db_connection = new mysqli($dbhost, $dbuser, $dbpassword, $database);
    if ($db_connection->connect_error) {
        die($db_connection->connect_error);
    }

    //retrieving data from courses table
    $course_query = "select Course, Applying_Undergraduate, Applying_Graduate, Accepted_Undergraduate, Accepted_Graduate from {$coursesTable} where Course = '{$course}'";
    $applying_TAs = [];
    $accepted_TAs = [];

    $result1 = $db_connection->query($course_query);
    if (!$result1) {
        die("Retrieval of courses failed: ". $db_connection->error);
    } else {
        $num_rows = $result1->num_rows;
        if ($num_rows === 0) {
            echo "Empty Table<br>";
        } else {
            $result1->data_seek(0);
            $row = $result1->fetch_array(MYSQLI_ASSOC);
            $applying_Undergraduate = unserialize($row["Applying_Undergraduate"]);
            $applying_Graduate = unserialize($row["Applying_Graduate"]);
            $applying_TAs = $applying_Graduate + $applying_Undergraduate;

            $accepted_Undergraduate = unserialize($row["Accepted_Undergraduate"]);
            $accepted_Graduate = unserialize($row["Accepted_Graduate"]);
            $accepted_TAs = $accepted_Undergraduate + $accepted_Graduate;
        }
    }


    //retrieving data from Applications table add constructing bodies of tables
    $fields = ['First', 'Last', 'Email', 'Directory_ID', 'GPA', 'Degree', "Previous","Transcript","Extra_Information" ];
    $fieldsQuery = implode(", ", $fields);
    $applications_query = "select {$fieldsQuery} from {$applicationsTable} order by {$sortby}";  

    $result2 = $db_connection->query($applications_query);
    if (!$result2) {
        die("Retrieval failed: ". $db_connection->error);
    } else {
        $num_rows = $result2->num_rows;

        if ($num_rows === 0) {
            echo "No Applications<br>";
        } else {
            for ($row_index = 0; $row_index < $num_rows; $row_index++) {
                $result2->data_seek($row_index);
                $row = $result2->fetch_array(MYSQLI_ASSOC);

                if (empty($applying_TAs) == false) {
                    if (in_array($row['Directory_ID'], $applying_TAs)) {
                        $applying_table .= "<tr>";
                        foreach($row as $columKey=>$columValue) {
                            if ($columKey == "Transcript") {
                                $applying_table .= "<td><input type='submit' class='btn btn-primary' value='Transcript {$row['Directory_ID']}' name ='Transcript {$row['Directory_ID']}'></td>";
                            } else if ($columKey == "Previous") {
                                $previous_course = unserialize($columValue);
                                if (count($previous_course) == 0) {
                                    $applying_table .= "<td>No Previous Experience</td>";
                                } else {
                                    $previous_course_str = implode(', ', $previous_course);
                                    $applying_table .= "<td>{$previous_course_str}</td>";
                                }

                            } else {
                                $applying_table .= "<td>{$columValue}</td>";
                            }
                        }
                    }
                }

                if (empty($accepted_TAs) == false) {
                    if (in_array($row['Directory_ID'], $accepted_TAs)) {
                        $accepted_table .= "<tr>";
                        foreach($row as $columKey=>$columValue) {
                            if ($columKey == "Transcript") {
                                $accepted_table .= "<td><input type='submit' class='btn btn-primary' value='Transcript {$row['Directory_ID']}' name ='Transcript {$row['Directory_ID']}'></td>";
                            } else if ($columKey == "Previous") {
                                $previous_course = unserialize($columValue);
                                if (count($previous_course) == 0) {
                                    $accepted_table .= "<td>No Previous Experience</td>";
                                } else {
                                    $previous_course_str = implode(', ', $previous_course);
                                    $accepted_table .= "<td>{$previous_course_str}</td>";
                                }

                            } else {
                                $accepted_table .= "<td>{$columValue}</td>";
                            }
                        }
                    }
                }
            }
        }
    }

    $applying_table .= "</table>";
    $accepted_table .= "</table>";

    if (empty($accepted_TAs)) {
        $accepted_table .= "<p> There are no TAs currently assigned to this class </p>";
    } 

    if (empty($applying_TAs)) {
        $applying_table .= "<p> There are no TAs currently applying to this class </p>";
    } 

    $applying_table .= "<hr style='height:1px;border:none;color:gray;background-color:#333;' /></form>";
    $accepted_table .= "<hr style='height:1px;border:none;color:gray;background-color:#333;' /></form>";

    $homeForm = <<<EOFORM
        <form action = "faculty.php" method='post' align="center">
        <input type="submit" class="btn btn-info" name="goback" value="Choose Another Course">
    </form>
EOFORM;

    $body = $head.$accepted_table.$applying_table."<br>".$homeForm;
    echo generatePage($body, "Display Administrative");
?>