<?php

/*
 * Copyright (C) 2018 The OpenExam Project
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

/*
 * Created: 2018-01-24 01:15:40
 * File:    CacheController.php
 *
 * Author:  Anders Lövgren (QNET)
 */

namespace OpenExam\Controllers\Utility;

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Models\Exam;
use UUP\Authentication\Restrictor\AddressRestrictor;

/**
 * Web and application cache controller.
 *
 * @author Anders Lövgren (QNET)
 */
class CacheController extends GuiController {

  /**
   * Fill cache with directory information.
   *
   * @param int $eid The exam ID.
   * @param int $days The number of days.
   * @throws Exception
   */
  public function fillAction($eid, $days = 3) {
    //
    // Don't load view:
    //
    $this->view->disable();

    //
    // Sanitize request parameters:
    //
    if (!($eid = $this->filter->sanitize($eid, "int"))) {
      throw new Exception("Expected eid parameter", Error::BAD_REQUEST);
    }
    if (!($days = $this->filter->sanitize($days, "int"))) {
      throw new Exception("Expected days parameter", Error::BAD_REQUEST);
    }

    //
    // Check method call restrictions:
    //
    if ($this->config->cache->filler->maxdays < $days) {
      throw new Exception("Exceeded maximum days config", Error::NOT_ACCEPTABLE);
    }

    $restrictor = new AddressRestrictor($this->config->cache->filler->remote);
    if (!$restrictor->match($this->request->getServerAddress())) {
      throw new Exception("You are not allowed to invoke this action", Error::METHOD_NOT_ALLOWED);
    }

    //
    // Fetch requested exam:
    //
    if (!($exam = Exam::findFirstById($eid))) {
      throw new Exception("Failed fetch exam");
    }

    //
    // Populate cache by query models and properties.
    //
    $this->fillCache($exam, $days);
  }

  /**
   * Fill exam cache.
   * @param Exam $exam The exam model.
   */
  private function fillCache($exam, $days) {
    $this->catalog->getCache()->setLifetime($days * 86400);

    $this->catalog->getName($exam->creator);
    $this->catalog->getMail($exam->creator);

    foreach ($exam->students as $model) {
      $this->catalog->getPrincipal($model->user);
    }
    foreach ($exam->contributors as $model) {
      $this->catalog->getPrincipal($model->user);
    }
    foreach ($exam->decoders as $model) {
      $this->catalog->getPrincipal($model->user);
    }
    foreach ($exam->invigilators as $model) {
      $this->catalog->getPrincipal($model->user);
    }
  }

}
