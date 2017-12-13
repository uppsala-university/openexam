<?php

use OpenExam\Library\Security\Roles;

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    sections.inc
// 
// Author:  Ahsan Shahzad (Medfarm DoIT)
// 

/**
 * Define page sections and section operations along with exam state based permissions.
 */
return array(
        Roles::CREATOR               => array(
                'title'        => array(
                        'name' => 'Creator',
                        'desc' => 'create and manage an exam'
                ),
                'show-options' => array(
                        'del-exam'    => array(
                                'target'        => 'del-exam',
                                'show-on-flags' => array(
                                        "deletable",
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
                        'reuse-exam'  => array(
                                'target'        => 'reuse-exam',
                                'show-on-flags' => array(
                                        'reusable'
                                )
                        ),
                        'manage-exam' => array(
                                'target'        => 'exam/update/{exam-id}/creator',
                                'show-on-flags' => array(
                                        "*"
                                )
                        ),
                        'check-exam'  => array(
                                'target'        => 'check-exam',
                                'show-on-flags' => array(
                                        '*'
                                )
                        )
                )
        ),
        Roles::CONTRIBUTOR           => array(
                'title'        => array(
                        'name' => 'Contributor',
                        'desc' => 'add questions to an exam'
                ),
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
                'title'        => array(
                        'name' => 'Invigilator',
                        'desc' => 'register students or change schedule'
                ),
                'show-options' => array(
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
                        ),
                        'check-exam'     => array(
                                'target'        => 'check-exam',
                                'show-on-flags' => array(
                                        '*'
                                )
                        )
                )
        ),
        Roles::CORRECTOR             => array(
                'title'        => array(
                        'name' => 'Corrector',
                        'desc' => 'correct the student\'s answers'
                ),
                'show-options' => array(
                        'download-result'   => array(
                                'target'        => 'render-decoder',
                                'show-on-flags' => array(
                                        "decoded", "enquiry"
                                )
                        ),
                        'view-scoreboard'   => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "enquiry",
                                        "decoded"
                                )
                        ),
                        'exam-not-finished' => array(
                                'target'        => '#',
                                'show-on-flags' => array(
                                        "upcoming",
                                        "running",
                                )
                        ),
                        'ans-correction'    => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "correctable"
                                )
                        )
                )
        ),
        Roles::DECODER               => array(
                'title'        => array(
                        'name' => 'Decoder',
                        'desc' => 'view and decode results'
                ),
                'show-options' => array(
                        'decode-closed'   => array(
                                'target'        => '#',
                                'show-on-flags' => array(
                                )
                        ),
                        'download-result' => array(
                                'target'        => 'render-decoder',
                                'show-on-flags' => array(
                                        "decoded", "enquiry"
                                )
                        ),
                        'view-scoreboard' => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "correctable",
                                        "decodable",
                                        "enquiry",
                                        "decoded"
                                )
                        ),
                        'decode-exam'     => array(
                                'target'        => 'exam/{exam-id}/correction',
                                'show-on-flags' => array(
                                        "decodable"
                                )
                        )
                )
        ),
        Roles::STUDENT . '-upcoming' => array(
                'title'        => array(
                        'name' => 'Student',
                        'desc' => 'your upcoming/ongoing exams'
                ),
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
                'title'        => array(
                        'name' => 'Student',
                        'desc' => 'your finished exams'
                ),
                'show-options' => array(
                        'download-result' => array(
                                'target'        => 'render-student',
                                'show-on-flags' => array(
                                        "decoded"
                                )
                        )
                )
        ),
);
