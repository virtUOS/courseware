<?php

class SurveyController extends CoursewareStudipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->plugin->getPluginPath() . '/assets/courseware.min.css');
        PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->plugin->getPluginPath() . '/assets/chart.css');
        PageLayout::addScript($this->plugin->getPluginURL() . '/assets/js/vendor/chartjs/Chart.js');
    }

    public function index_action()
    {
        if (Navigation::hasItem('/course/mooc_survey')) {
            Navigation::activateItem("/course/mooc_survey");
        }
        $this->mode = $GLOBALS['perm']->have_studip_perm("tutor", $this->plugin->getCourseId()) ? 'total' : 'single';
        $this->members = CourseMember::findByCourseAndStatus($this->plugin->getCourseId(), 'autor');
        $this->test_title = array();
        $this->test_type = array();
        $this->survey = $this->getSurveys();
    }

    public function getTestTitle($test_id, $exercise_id)
    {
        return $this->test_title[$test_id][$exercise_id];
    }

    public function getTestType($test_id, $exercise_id)
    {
        return $this->test_type[$test_id][$exercise_id];
    }
    
    public function getFullTestTypeName($uri)
    {
        switch ($uri){
            case "rh_exercise":
                return _cw("Zuordnungsaufgabe");
                break;
            case "sc_exercise":
                return _cw("Single-Choice-Aufgabe");
                break;
            case "mc_exercise":
                return _cw("Multiple-Choice-Aufgabe");
                break;
            case "lt_exercise":
                return _cw("Freitextaufgabe");
                break;
        }
        return $uri;
    }

    private function getSurveys()
    {
        $cid = $this->container['cid'];
        $subtype = "survey";
        
        $db = \DBManager::get();
        $stmt = $db->prepare('SELECT id 
                              FROM mooc_blocks 
                              WHERE seminar_id = :cid AND sub_type = :subtype');
        $stmt->bindParam(':cid', $cid);
        $stmt->bindParam(':subtype', $subtype);
        $stmt->execute();
        $block_ids = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $test_ids = array();

        foreach ($block_ids as $block_id) {
            $id = $block_id['id'];
            $stmt = $db->prepare('SELECT json_data 
                                  FROM mooc_fields 
                                  WHERE block_id = :block_id');
            $stmt->bindParam(':block_id', $id);
            $stmt->execute();
            $test_id = $stmt->fetch(\PDO::FETCH_ASSOC);
            array_push($test_ids, $test_id["json_data"]);
        }

        $test_aggregation = array();
        foreach ($test_ids as $test_id) {
            $test_data = array();
            $this->test_title[$test_id] = array();
            $this->test_type[$test_id] = array();
            if ($test_id != null) {
                $test_id = (int) json_decode($test_id);
                $stmt = $db->prepare('SELECT Aufgabe, Name, URI, solution, exercise_id 
                                      FROM vips_aufgabe 
                                      INNER JOIN vips_solution 
                                      ON vips_aufgabe.ID =  vips_solution.exercise_id 
                                      WHERE test_id = :test_id');
                $stmt->bindParam(':test_id', $test_id);
                $stmt->execute();
                $test_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $aggregation = array();
                $aggregation_task = array();
                $aggregation_type = array();
                foreach($test_data as $testkey => $test) {
                    $aggregation_task[$test["exercise_id"]] = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', preg_replace('/<Paragraph>[^>]+<\/Paragraph>/i', " ", $test["Aufgabe"]));
                    $this->test_title[$test_id][$test["exercise_id"]] =  $test["Name"];
                    $this->test_type[$test_id][$test["exercise_id"]] =  $test["URI"];
                    if (!(array_key_exists($test["exercise_id"],$aggregation))) {
                        $aggregation[$test["exercise_id"]] = array();
                        $aggregation_type[$test["exercise_id"]]  = $test["URI"] ;
                    }
                     array_push($aggregation[$test["exercise_id"]],$test["solution"]);
                }

                foreach ($aggregation as $key => $value) {
                    switch ($aggregation_type[$key]) {
                        case "lt_exercise":
                            $aggregation[$key] = $this->aggregateLT($value);
                            break;
                        case "sc_exercise":
                            $aggregation[$key] = $this->aggregateSC($value, $aggregation_task[$key]);
                            break;
                        case "mc_exercise":
                            $aggregation[$key] = $this->aggregateMC($value, $aggregation_task[$key]);
                            break;
                        case "rh_exercise":
                            $aggregation[$key] = $this->aggregateRH($value, $aggregation_task[$key]);
                            
                            break;
                        default:
                            $aggregation[$key] = _vips("Daten können nicht zusammengefasst werden");
                    }
                }
                $test_aggregation[$test_id] = $aggregation;
            }
        }
        return $test_aggregation;
    }

    private function aggregateLT($array)
    {
        $agr_array = array();
        foreach ($array as &$item) {
            array_push( $agr_array , (string) simplexml_load_string($item)->answer->body);
        }
        return $agr_array;
    }

    private function aggregateSC($array, $task)
    {
        $agr_array = array();
        foreach ($array as $value) {
           
            $value = (int) simplexml_load_string($value)->answer->body;
            
            if ((array_key_exists($value,$agr_array))) {
                $agr_array[$value] = $agr_array[$value] +1;
            } 
            else {
                $agr_array[$value] = 1;
            }
        }
        foreach ((array)simplexml_load_string($task)->Answer as $key =>$answer) {
            if(is_int($key)) {
                $agr_array[$answer] = $agr_array[$key];
                unset($agr_array[$key]);
            }
            if ($agr_array[$answer] == null) {
                $agr_array[$answer] = 0;
            }
        }
        
        return $agr_array;
    }

    private function aggregateMC($array, $task)
    {
        $agr_array = array();
       
        foreach ($array as $value) {
           foreach(simplexml_load_string($value)->answer as $answer) { 
                $answer_id = (int)$answer->attributes()->id;
                $answer_value = (int) $answer->body;
                if ($answer_value == 1) {
                    if ((array_key_exists($answer_id,$agr_array))) {
                        $agr_array[$answer_id] = $agr_array[$answer_id] +1;
                    } 
                    else {
                       $agr_array[$answer_id] = 1;
                    }
                }
            }
        }

        foreach ((array)simplexml_load_string($task)->Answer as $key =>$answer) {
            if(is_int($key)) {
                $agr_array[$answer] = $agr_array[$key];
                unset($agr_array[$key]);
            }
            if ($agr_array[$answer] == null) {
                $agr_array[$answer] = 0;
            }
        }

        return $agr_array;
    }

    private function aggregateRH($array, $task)
    {
        $task = $this->encodeSpecialCharacters($task);
        $agr_array = array();
        $i = 0;
        foreach (simplexml_load_string($array[0])->answer as $item) {
            $agr_array[$i] = 0;
            $i++;
        }
        
        foreach ($array as $value) {
            foreach(simplexml_load_string($value)->answer as $answer) { 
                $answer_id = (int)$answer->attributes()->id;
                $user_value = (int) $answer->body;
                $agr_array[$answer_id] = $this->valuateRH ($user_value,  $agr_array[$answer_id], $answer_id);
            }
        }

        $answers = 0;

        foreach ((array)simplexml_load_string($task)->Answer as $key => $answer) {
            if(is_int($key)) {
                $answer = ($this->decodeSpecialCharacters($answer));
                $agr_array[$answer] = $agr_array[$key];
                unset($agr_array[$key]);
                $answers++;
            }

            if ($agr_array[$answer] == null) {
                $agr_array[$answer] = 0;
            }
        }

        foreach ((array)simplexml_load_string($task)->FalseAnswer as $key => $false_answer) {
            $key = $key + $answers;
            $false_answer = ($this->decodeSpecialCharacters($false_answer));
            $agr_array[$false_answer] = $agr_array[$key];
            unset($agr_array[$key]);
            if ($agr_array[$false_answer] == null) {
                $agr_array[$false_answer] = 0;
            }
        }

        return ($agr_array);
    }
    
    private function valuateRH ($user_value, $array_value, $answer_id)
    {
        if ($user_value != -1) {
            $array_value = pow(0.5, $user_value); 
        }

        return $array_value;
    }
    
    
    //encodeSpecialCharacters  und decodeSpecialCharacters wird benötigt, 
    //da simplexml nur UTF-8 verarbeiten kann. htmlspecialchars funktioniert 
    //wegen des "&" Zeichens auch nicht. 

    private function encodeSpecialCharacters($string)
    {
        $string = str_replace('Ä', '+Auml;', $string);
        $string = str_replace('ä', '+auml;', $string);
        $string = str_replace('Ö', '+Ouml;', $string);
        $string = str_replace('ö', '+ouml;', $string);
        $string = str_replace('Ü', '+Uuml;', $string);
        $string = str_replace('ü', '+uuml;', $string);
        $string = str_replace('ß', '+szlig;', $string);

     return $string;
    }
    
    private function decodeSpecialCharacters($string)
    {
        $string = str_replace('+Auml;', 'Ä', $string);
        $string = str_replace('+auml;', 'ä', $string);
        $string = str_replace('+Ouml;', 'Ö', $string);
        $string = str_replace('+ouml;', 'ö', $string);
        $string = str_replace('+Uuml;', 'Ü', $string);
        $string = str_replace('+uuml;', 'ü', $string);
        $string = str_replace('+szlig;', 'ß', $string);

     return $string;
    }

}


