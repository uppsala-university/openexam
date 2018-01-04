<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    History.php
// Created: 2016-04-27 20:08:18
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\User\Component;

/**
 * Model version control based on audit data.
 * 
 * Provides basic version control for a model object by applying changes
 * from audit data. Even though this class modifies the model, its never
 * actually persisting the model to storage.
 * 
 * <code>
 * // Work on history for an answer model object:
 * $history = new History($answer);
 * 
 * // Apply undo/redo operations:
 * $history->undo();
 * $history->redo();
 * 
 * // Get all revisions:
 * $history->revisions();
 * 
 * // Revert model back to revision $id:
 * $history->revert($id);
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class History extends Component
{

        /**
         * @var ModelInterface 
         */
        private $_model;
        /**
         * Array of revisions.
         * @var array 
         */
        private $_rrevs;
        /**
         * Current revision index.
         * @var int 
         */
        private $_index;

        /**
         * Constructor.
         * @param ModelInterface $model The model object.
         */
        public function __construct($model)
        {
                $this->_model = $model;
                $this->_rrevs = $this->audit->getRevisions($model);
                $this->_index = count($this->_rrevs) - 1;
        }

        /**
         * Get model object.
         * @return ModelInterface
         */
        public function getModel()
        {
                return $this->_model;
        }

        /**
         * Get current revision index position.
         * @return int
         */
        public function getPosition()
        {
                return $this->_index;
        }

        /**
         * Get current revision ID.
         * @return int
         */
        public function getIndex()
        {
                return intval($this->_rrevs[$this->_index]['id']);
        }

        /**
         * Get number of revisions.
         * @return int
         */
        public function getSize()
        {
                return count($this->_rrevs);
        }

        /**
         * Get revision for current index position.
         * @return array
         */
        public function getRevision()
        {
                return $this->_rrevs[$this->_index];
        }

        /**
         * Get changes in current revision.
         * @return array
         */
        public function getChanges()
        {
                return unserialize($this->_rrevs[$this->_index]['changes']);
        }

        /**
         * Return true if model has revisions.
         * @return boolean
         */
        public function hasRevisions()
        {
                return is_array($this->_rrevs);
        }

        /**
         * Return position of given revision or -1 if missing.
         * @param int $id The revision ID.
         */
        public function hasRevision($id)
        {
                for ($i = 0; $i < count($this->_rrevs); ++$i) {
                        if ($this->_rrevs[$i]['id'] == $id) {
                                return intval($i);
                        }
                }

                return -1;
        }

        /**
         * Get all revisions.
         * @return array
         */
        public function getRevisions()
        {
                return $this->_rrevs;
        }

        /**
         * Undo model changes.
         */
        public function undo()
        {
                $this->apply('old');
                if ($this->_index > 0) {
                        $this->_index--;
                }
        }

        /**
         * Redo model changes.
         */
        public function redo()
        {
                if ($this->_index < count($this->_rrevs) - 1) {
                        $this->_index++;
                }
                $this->apply('new');
        }

        /**
         * Revert changes back to revision $id.
         * 
         * Call this function to revert model state to changes applied in given 
         * revision. All previous revisions are applied ierative until ending up 
         * on requested revision. Return true on sucessful.
         * 
         * If requested revision is current, then true is returned even though
         * no state is actually reverted.
         * 
         * By default, the state is restored to new state in each revision. 
         * Call revert($id, true) to use previous state from each revision 
         * instead of last.
         * 
         * @param int $id The revision ID.
         * @param boolean $previous Use previous state instead of last.
         * @return boolean Return true if successful.
         */
        public function revert($id, $previous = false)
        {
                if (($pos = $this->hasRevision($id)) == -1) {
                        return false;
                }
                if ($pos > $this->_index) {
                        return false;
                }
                if ($pos == $this->_index && $previous == false) {
                        return true;
                }

                while ($this->_index != $pos) {
                        $this->undo();
                }
                if ($previous) {
                        $this->undo();
                }

                return true;
        }

        /**
         * Resync audit revisions.
         */
        public function refresh()
        {
                $this->_rrevs = $this->audit->getRevisions($this->_model);
        }

        /**
         * Save model changes.
         * @return boolean
         */
        public function save()
        {
                return $this->_model->save();
        }

        /**
         * Apply diff to model.
         * @param string $direction The direction (new or old).
         */
        private function apply($direction)
        {
                foreach ($this->getChanges() as $key => $val) {
                        $this->_model->$key = $val[$direction];
                }
        }

}
