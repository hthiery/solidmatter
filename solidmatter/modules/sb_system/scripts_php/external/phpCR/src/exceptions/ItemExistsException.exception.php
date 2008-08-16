<?php
// $Id: ItemExistsException.exception.php 399 2005-08-13 19:38:08Z tswicegood $

/**
 * This file contains {@link ItemExistsException} which is part of the PHP
 * Content Repository (phpCR), a derivative of the Java Content Repository 
 * JSR-170,  and is licensed under the Apache License, Version 2.0.
 *
 * This file is based on the code created for
 * {@link http://www.jcp.org/en/jsr/detail?id=170 JSR-170}
 *
 * @author Travis Swicegood <development@domain51.com>
 * @copyright PHP Code Copyright &copy; 2004-2005, Domain51, United States
 * @copyright Original Java and Documentation 
 *    Copyright &copy; 2002-2004, Day Management AG, Switerland
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, 
 *    Version 2.0
 * @package phpContentRepository
 */


/**
 * Require the necessary file(s)
 */
require_once dirname(__FILE__) . '/../phpCR.library.php';
require_once PHPCR_PATH . '/exceptions/RepositoryException.exception.php';


/**
 * An exception thrown when an attempt is made to place an item in a position
 * where another item already exists.
 *
 * @package phpContentRepository
 */
class ItemExistsException extends RepositoryException
{
    // No need to define anything, it inherits what it needs to.
}

