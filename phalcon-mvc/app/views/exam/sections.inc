<?php

use OpenExam\Library\Security\Roles;

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
