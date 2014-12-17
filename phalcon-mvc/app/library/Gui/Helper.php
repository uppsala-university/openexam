<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Helper.php
// Created: 2014-11-06 17:50:05
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Gui;

use Phalcon\Mvc\User\Component;
/**
 * Views helper component
 * Formats data to be easily useable in views
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class Helper extends Component
{

        /**
         * Format data returned from catalog service to make it useable in views,
         * in a better and clean way
         *
         * @param string $principal
         * @param string $attribute
         * @param string $firstOnly Return only first record, if set true
         * @return array
         */
        public function getCatalogAttribute($principal, $attribute, $firstOnly = TRUE)
        {
                $data = array();
                $userData = $this->catalog->getAttribute($principal, $attribute);
                
                //search for info from all services
                foreach($userData as $service) {
                        $data = $userData[0][$attribute];
                        if(count($data)) {
                                break;
                        }
                }
                
                return $firstOnly ? $data[0] : $data;
        }
       
        /**
         * Get question number by question id
         *
         * @param array $questions
         * @return array
         
        public function getQuestionNumber($questions)
        {
                $data = array();
                
                //search for info from all services
                foreach($questions as $index => $question) {
                        $data = $userData[0][$attribute];
                        if(count($data)) {
                                break;
                        }
                }
                
                return $firstOnly ? $data[0] : $data;
        }*/
       
}
