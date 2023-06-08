<?php 

    function connect(){
        $mysqli = new mysqli('localhost','root','','scrape');
        if($mysqli->connect_errno != 0){
            return $mysqli->connect_error;
        }
        else{
            return $mysqli;
        }
    }

    function getAllDoctors(){
        $mysqli = connect();
        
        $res =  $mysqli->query("SELECT doctor_name, doc_photo FROM doctors ORDER BY doctor_name ASC");
        while($row = $res->fetch_assoc()){
            $doctors[] = $row;
        }
        return $doctors;
    }

    function getAllExpertise(){
        $mysqli = connect();
        $res =  $mysqli->query("SELECT * FROM expertise ORDER BY expertise_name ASC");
        while($row = $res->fetch_assoc()){
            $expertise[] = $row;
        }
        return $expertise;
    }
    
    function getDoctorsByExpertise($gelenProfession){
        $mysqli = connect();
        $query = "SELECT dc.doctor_name, dc.doc_photo
                  FROM doctors AS dc
                  INNER JOIN doc_expertise AS de ON dc.doctor_id = de.doctor_id
                  INNER JOIN expertise AS exp ON de.expertise_id = exp.expertise_id
                  WHERE exp.expertise_id = $gelenProfession";
        $res = $mysqli->query($query);
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            echo "No doctor found";
        }
        return $result;
    }


    function doctor_box($doctor){
            echo '<div class="col-lg-6 team-item">';
            echo '<a name="doctor" href="doc-personal-page.php?doctor='.$doctor['doctor_name'].'">';
                echo  '<div class="row g-0 bg-light rounded overflow-hidden">';

                        echo    '<div class="col-12 col-sm-5 h-100">
                                    <img class="img-fluid h-100" src="'.$doctor['doc_photo'].'" style="object-fit: cover;">
                                </div>';

                        echo    '<div class="col-12 col-sm-7 h-100 d-flex flex-column">
                                <div class="mt-auto p-4">
                                    <h3>'.$doctor['doctor_name'].'</h3>
                                    <h6 class="fw-normal fst-italic text-primary mb-4">',getExpertiseByDoc($doctor),'</h6>
                                    <p style = "color:orange;"class="m-0">',getRateofDoc($doctor),'</p>
                                </div>';   
                        echo   '</div>';
                echo  '</div>';
            echo '</a>';
            echo '</div>';
    }

    function getExpertiseByDoc($doctor){
        $mysqli = connect();
        if(gettype($doctor) == 'string'){
            $doc = $doctor;
            $query = "SELECT exp.expertise_name
                  FROM expertise AS exp
                  INNER JOIN doc_expertise AS de ON exp.expertise_id = de.expertise_id
                  INNER JOIN doctors AS dc ON de.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doc'";
            $res = $mysqli->query($query);
        }
        else{
            $doc = $doctor['doctor_name'];
            $query = "SELECT exp.expertise_name
                  FROM expertise AS exp
                  INNER JOIN doc_expertise AS de ON exp.expertise_id = de.expertise_id
                  INNER JOIN doctors AS dc ON de.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doc'";
            $res = $mysqli->query($query);
        }
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            echo "No doctor found";
        }
        foreach($result as $expertise){
            echo $expertise['expertise_name'] . '  ';
        }
    }
    
    function getRateofDoc($doctor){
        $mysqli = connect();
        if(gettype($doctor) == 'array'){
            $doc = $doctor['doctor_name'];
            $query = "SELECT AVG(r.rating) AS rate
                  FROM ratings AS r
                  INNER JOIN doctors AS dc ON r.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doc'";
            $res = $mysqli->query($query);
            echo number_format($res->fetch_assoc()['rate'],1,".",'');
        }
        else if(gettype($doctor) == 'string'){
            $doc = $doctor;
            $query = "SELECT AVG(r.rating) AS rate
                  FROM ratings AS r
                  INNER JOIN doctors AS dc ON r.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doc'";
            $res = $mysqli->query($query);
            echo number_format($res->fetch_assoc()['rate'],1,".",'');
        }
        else{
            $doc = $doctor;
            $query = "SELECT AVG(r.rating) AS rate
                  FROM ratings AS r
                  INNER JOIN doctors AS dc ON r.doctor_id = dc.doctor_id
                  WHERE dc.doctor_id = $doc";
            $res = $mysqli->query($query);
            echo number_format($res->fetch_assoc()['rate'],1,".",'');
        }
    }


    function getCommentInfo($doctor){
        $mysqli = connect();
        $query = "SELECT r.patient_name, r.rating, r.comment, r.date
                  FROM ratings AS r
                  INNER JOIN doctors AS dc ON r.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doctor'";
        $res = $mysqli->query($query);
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $result[] = $row;
            }
            return $result;
        }
    }
    function getPageInfo($doctor){
        echo '<!-- Blog Detail Start -->
                <div class="mb-5">
                    <img class="img-fluid w-100 rounded mb-5" src="',get_image($doctor),'" alt="">
                    <h1 class="mb-4">'.$doctor.'</h1>
                    <h2 style="color:#2596be">',getExpertiseByDoc($doctor),'</h2><br>
                    <h3>Tedavi Edilen Hastalıklar</h3>
                    <p>',getTreatments($doctor),'</p>
                    <div class="d-flex justify-content-between bg-light rounded p-4 mt-4 mb-4">
                        <div class="d-flex align-items-center">
                            <img class="rounded-circle me-2" src="',get_image($doctor),'" width="40" height="40" alt="">
                            <span>'.$doctor.'</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="ms-3">',getRateofDoc($doctor),'    <i style="color:orange;" class="fa-solid fa-star"></i></span>
                        </div>
                    </div>
                </div>
                <!-- Blog Detail End -->

                <!-- Comment List Start -->
                <div class="mb-5">
                    <h4 class="d-inline-block text-primary text-uppercase border-bottom border-5 mb-4">Comments</h4>
                    ',showComment($doctor),'
                </div>';
    }

    function getTreatments($doctor){
        $mysqli = connect();
        $query = "SELECT dg.diagnose_name
                  FROM doc_diagnoses AS dg
                  INNER JOIN doctors AS dc ON dg.doctor_id = dc.doctor_id
                  WHERE dc.doctor_name = '$doctor'";
        $res = $mysqli->query($query);
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            echo "No data found";
        }
        foreach($result as $treatment){
            echo $treatment['diagnose_name'] . ' <br> ';
        }
    }

    function showComment($doctor){
        $result = getCommentInfo($doctor);
        if ($result) {
            foreach($result as $comment){
                echo '<div class="d-flex mb-4">
                        <img src="img/user.png" class="img-fluid rounded-circle" style="width: 45px; height: 45px;">
                        <div class="ps-3">
                            <h6>'.$comment['patient_name'].'<small><i>',$comment['date'],'</i></small>             <i style="color:orange;" class="fa-solid fa-star"></i>    
                                  ('.$comment['rating'].' / 5)</h6>
                            <p>',$comment['comment'],'</p>
                        </div>
                </div>';
            }
        } else {
            echo "<br>No Comment.";
        }
    }

    function get_image($doctor){
        $mysqli = connect();
        $query = "SELECT doc_photo
                  FROM doctors
                  WHERE doctor_name = '$doctor'";
        $res = $mysqli->query($query);
        $result = $res->fetch_assoc();
        echo $result['doc_photo'];
    }

    function getDoctorsByName($name){
        $mysqli = connect();
        $query = "SELECT doctor_name, doc_photo
                  FROM doctors
                  WHERE doctor_name LIKE '%$name%'";
        $res = $mysqli->query($query);
        try{
            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $result[] = $row;
                }
                return $result;
            }
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
    }



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Detail</title>
    <link href="css/style.css" rel="stylesheet">
    
</head>
<body>
</body>
</html>