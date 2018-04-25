<?php
    require_once "support.php";
    require_once "dblogin.php";
    require_once "courses.php";

    //retrieving fields from form session
    session_start();

    /*
    $applicationsTable = $_SESSION["applicationsTable"];
    $coursesTable = $_SESSION["coursesTable"];
    $course = $_SESSION["course"];
    */

    $applicationsTable = "Applications_Spring_2018";
    $coursesTable = "Courses_Spring_2018";
    

    // connecting to database;
    $db_connection = new mysqli($dbhost, $dbuser, $dbpassword, $database);
    if ($db_connection->connect_error) {
        die($db_connection->connect_error);
    }

    $course_query = "select Course, Applying_Undergraduate, Applying_Graduate, Accepted_Undergraduate, Accepted_Graduate, Max_Undergraduate, Max_Graduate, Max_Total from {$coursesTable} order by 'Course'";

    $result0 = mysqli_query($db_connection, $course_query);
    if (!$result0) {
        die("Retrieval of courses failed: ". $db_connection->error);
    } else {
        $num_rows_course = $result0->num_rows;
        if ($num_rows_course === 0) {
            echo "Empty Table<br>";
        } else {
            //iterating through the courses
            for ($course_index = 0; $course_index < 5; $course_index++) {
                $result0->data_seek($course_index);
                $a_course = $result0->fetch_array(MYSQLI_ASSOC);
                $currName = $a_course['Course'];

                echo $currName." ".$course_index."<br>";

                //initializing the data for the current course
                $new_Max_Ugrad = $a_course["Max_Undergraduate"];
                $new_Max_Grad = $a_course["Max_Graduate"];

                $applying_Undergraduate = unserialize($a_course["Applying_Undergraduate"]);
                $applying_Graduate = unserialize($a_course["Applying_Graduate"]);

                $accepted_Undergraduate = unserialize($a_course["Accepted_Undergraduate"]);
                $accepted_Graduate = unserialize($a_course["Accepted_Graduate"]);

                if (empty($applying_Undergraduate)) {
                    $applying_Undergraduate = [];
                }
                if (empty($applying_Graduate)) {
                    $applying_Graduate = [];
                }
                if (empty($accepted_Undergraduate)) {
                    $accepted_Undergraduate = [];
                }
                if (empty($accepted_Graduate)) {
                    $accepted_Graduate = [];
                }

                $applications_query = "select Directory_ID, GPA, Degree from {$applicationsTable} order by GPA DESC";

                $result2 = mysqli_query($db_connection, $applications_query);
                if (!$result2) {
                    die("Retrieval failed: ". $db_connection->error);
                } else {
                    $num_rows = $result2->num_rows;

                    if ($num_rows === 0) {
                        echo "No Applications<br>";
                    } else {
                        $addedUndergrad = 0;
                        $addedGrad = 0;

                        for ($row_index = 0; $row_index < $num_rows; $row_index++) {
                            $result2->data_seek($row_index);
                            $row = $result2->fetch_array(MYSQLI_ASSOC);

                            if ($addedUndergrad >= $new_Max_Ugrad && $addedGrad >= $new_Max_Grad) {
                                break;
                            }

                            if ($row["Degree"] == "Undergraduate" && $addedUndergrad < $new_Max_Ugrad) {
                                array_push($accepted_Undergraduate, $row["Directory_ID"]);
                                $addedUndergrad = $addedUndergrad + 1;
                            } else if ($addedGrad < $new_Max_Grad) {
                                array_push($accepted_Graduate, $row["Directory_ID"]);
                                $addedGrad = $addedGrad + 1;
                            }
                        }

                    }

                    $updated_applying_u = [];
                    foreach($applying_Undergraduate as $key => $value) {
                        if (!(in_array($value, $accepted_Undergraduate))) {
                            array_push($updated_applying_u, $value);
                        }
                    }

                    $updated_applying_g = [];
                    foreach($applying_Graduate as $key => $value) {
                        if (!(in_array($value, $accepted_Graduate))) {
                            array_push($updated_applying_g, $value);
                        }
                    }

                    $final_applying_undergraduate = serialize($updated_applying_u);
                    $final_applying_graduate = serialize($updated_applying_g);
                    $final_accepted_undergraduate = serialize($accepted_Undergraduate);
                    $final_accepted_graduate = serialize($accepted_Graduate);

                    $update_query = "update {$coursesTable} ";
                    $update_query .= "set Applying_Undergraduate = '".$final_applying_undergraduate."', Applying_Graduate = '".$final_applying_graduate."', Accepted_Undergraduate = '".$final_accepted_undergraduate."', Accepted_Graduate = '".$final_accepted_graduate."' ";
                    $update_query .= "where Course = '{$currName}'";


                    $result3 = $db_connection->query($update_query);
                    if (!$result3) {
                        die("Retrieval of courses failed: ". $db_connection->error);
                    } else {
                        echo $currName;
                        echo "<br>";
                        print_r($updated_applying_u);
                        echo "<br>";
                        print_r($updated_applying_g);
                        echo "<br>";
                        print_r($accepted_Undergraduate);  
                        echo "<br>";
                        print_r($accepted_Graduate);
                        echo "<br>";
                    }
                }

            }
        }
    }
    
?>