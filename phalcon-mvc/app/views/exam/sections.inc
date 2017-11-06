<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    sections.inc
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// 

use OpenExam\Library\Security\Roles;

/**
 * Define page sections and section operations along with exam state based permissions.
 */
return array(
        Roles::CREATOR               => array(
                'title'        => 'Creator - create and manage an exam',
                'show-options' => array(
                        'check-exam'  => array(
                                'target'        => 'check-exam',
                                'show-on-flags' => array(
                                        '*'
                                )
                        ),
                        'del-exam'    => array(
                                'target'        => 'del-exam',
                                'show-on-flags' => array(
                                        "deletable",
                                )
                        ),
                        'reuse-exam'  => array(
                                'target'        => 'reuse-exam',
                                'show-on-flags' => array(
                                        'reusable'
                                )
                        ),
                        'test-exam'   => array(
                                'target'        => 'exam/{exam-id}',
                                'show-on-flags' => array(
                                        "contributable"
                                )
                        ),
                        'view-exam'   => array(
                                'target'        => 'exam/{exam-id}/question/all',
                                'show-on-flags' => array(
                                        "published"
                                )
                        ),
                        'manage-exam' => array(
                                'target'        => 'exam/update/{exam-id}/creator',
                                'show-on-flags' => array(
                                        "*"
                                )
                        )
                )
        ),
        Roles::CONTRIBUTOR           => array(
                'title'        => 'Contributor - add questions to an exam',
                'show-options' => array(
                        'add-q'     => array(
                                'target'        => 'exam/update/{exam-id}/contributor/add-q',
                                'show-on-flags' => array(
                                        "contributable"
                                )
                        ),
                        'view-exam' => array(
                                'target'        => 'exam/update/{exam-id}/contributor',
                                'show-on-flags' => array(
                                        '*'
                                )
                        )
                )
        ),
        Roles::INVIGILATOR           => array(
                'title'        => 'Invigilator',
                'show-options' => array(
                        'check-exam'     => array(
                                'target'        => 'check-exam',
                                'show-on-flags' => array(
                                        '*'
                                )
                        ),
                        'change-time'    => array(
                                'target'        => 'change-time',
                                'show-on-flags' => array(
                                        "examinatable"
                                )
                        ),
                        'manage-student' => array(
                                'target'        => 'manage-students',
                                'show-on-flags' => array(
                                        "examinatable"
                                )
                        )
                )
        ),
        Roles::CORRECTOR             => array(
                'title'        => 'Corrector - correct the student\'s answers',
                'show-options' => array(
                        'download-result'   => array(
                                'target'        => 'exam/{exam-id}/correction/download-result',
                                'show-on-flags' => array(
                                        "decoded"
                                )
                        ),
                        'view-scoreboard'   => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "decoded"
                                )
                        ),
                        'ans-correction'    => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "correctable"
                                )
                        ),
                        'exam-not-finished' => array(
                                'target'        => '#',
                                'show-on-flags' => array(
                                        "upcoming",
                                        "running",
                                )
                        ),
                )
        ),
        Roles::DECODER               => array(
                'title'        => 'Decoder - view and decode results',
                'show-options' => array(
                        'download-result' => array(
                                'target'        => 'exam/{exam-id}/correction/download-results',
                                'show-on-flags' => array(
                                        "decoded"
                                )
                        ),
                        'view-scoreboard' => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "correctable",
                                        "decodable",
                                        "decoded"
                                )
                        ),
                        'decode-exam'     => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "decodable"
                                )
                        ),
                        'decode-closed'   => array(
                                'target'        => '#',
                                'show-on-flags' => array(
                                )
                        ),
                )
        ),
        Roles::STUDENT . '-upcoming' => array(
                'title'        => 'Your upcoming/ongoing exams',
                'show-options' => array(
                        'st-exam-page' => array(
                                'target'        => 'exam/{exam-id}',
                                'show-on-flags' => array(
                                        "*"
                                )
                        )
                )
        ),
        Roles::STUDENT . '-finished' => array(
                'title'        => 'Your finished exams',
                'show-options' => array(
                        'download-result' => array(
                                'target'        => 'result/{exam-id}/download',
                                'show-on-flags' => array(
                                        "decoded"
                                )
                        )
                )
        ),
);
