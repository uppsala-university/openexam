<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Request.php
// Created: 2017-09-14 14:09:00
//
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
//

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Native as NativeCache;
use OpenExam\Library\Database\Cache\Mediator\Storage\Shared as SharedStorage;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * The request mediator.
 *
 * All result sets from queries are cached for the duration of the request.
 * When terminating, all cached keys (and its data) are removed.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Request extends MediatorHandler implements MediatorInterface {

  /**
   * The key store.
   * @var SharedStorage
   */
  private $_store;

  /**
   * Constructor.
   * @param AdapterInterface $adapter The database adapter.
   * @param BackendInterface $cache The query cache.
   */
  public function __construct($adapter, $cache) {
    parent::__construct($adapter, new NativeCache($cache));
    $this->_store = new SharedStorage();
  }

  public function __destruct() {
    $this->flush();
  }

  public function setCache($cache) {
    $this->_cache = new NativeCache($cache);
  }

  public function store($keyName, $content, $tables) {
    $this->_cache->save($keyName, $content, $tables);
    $this->_store->add($keyName);
  }

  /**
   * Flush all cached keys.
   */
  private function flush() {
    foreach ($this->_store->keys() as $keyName) {
      $this->_cache->delete($keyName);
      $this->_store->remove($keyName);
    }
  }

}
