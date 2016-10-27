<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Component.php
// Created: 2016-10-27 11:53:07
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Gui;

/**
 * Component interface.
 * 
 * Components are objects that can be rendered to HTML. These are typical
 * small classes that adds UI.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface Component
{

        /**
         * Render this component.
         */
        function render();
}
