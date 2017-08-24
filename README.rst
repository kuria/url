Url
###

Parsing, modifying and building URLs.

.. contents::


Features
********

- parsing URLs
- determining the current URL from ``$_SERVER`` properties
- building relative and absolute URLs, including protocol-relative URLs
- getting, checking and setting individual URL components:

  - scheme
  - host
  - port
  - user
  - password
  - path
  - query parameters
  - fragment


Requirements
************

- PHP 7.1+


Usage
*****

Getting the current URL
=======================

The current URL is determined using ``$_SERVER`` properties.

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = Url::current();

   echo $url;

Example output:

::

  http://localhost/test.php


Creating a new URL
==================

Create a new instance of ``Url`` and use constructor arguments or setters
to define the components:

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = new Url();

   $url->setScheme('http');
   $url->setHost('example.com');
   $url->setPath('/test');
   // many more setters are available..

   echo $url;

Output:

::

  http://example.com/test


Parsing an URL
==============

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = Url::parse('http://bob:123456@example.com:8080/test?foo=bar&lorem=ipsum#fragment');


Getting URL components
======================

.. code:: php

   var_dump(
       $url->getScheme(),
       $url->getUser(),
       $url->getPassword(),
       $url->getHost(),
       $url->getFullHost(),
       $url->getPort(),
       $url->getPath(),
       $url->getQuery(),
       $url->getFragment()
   );

   // checking whether a certain component is defined
   var_dump(
       $url->hasScheme(),
       $url->hasUser(),
       $url->hasPassword(),
       $url->hasHost(),
       $url->hasPort(),
       $url->hasPath(),
       $url->hasQuery(),
       $url->hasFragment()
   );


Output:

::

  string(4) "http"
  string(3) "bob"
  string(6) "123456"
  string(11) "example.com"
  string(16) "example.com:8080"
  int(8080)
  string(5) "/test"
  array(2) {
    ["foo"]=>
    string(3) "bar"
    ["lorem"]=>
    string(5) "ipsum"
  }
  string(8) "fragment"
  bool(true)
  bool(true)
  bool(true)
  bool(true)
  bool(true)
  bool(true)
  bool(true)
  bool(true)


Getting query parameters
========================

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = Url::parse('/test?foo=bar&lorem%5B0%5D=ipsum&lorem%5B1%5D=dolor');

   var_dump(
       $url->has('foo'),
       $url->has('nonexistent'),
       $url->get('foo'),
       $url->get('lorem')
   );

Output:

::

  bool(true)
  bool(false)
  string(3) "bar"
  array(2) {
    [0]=>
    string(5) "ipsum"
    [1]=>
    string(5) "dolor"
  }


Falling back to a default value
-------------------------------

Attempting to ``get()`` an undefined query parameter will result in an exception. If
you want to fall back to a default value in such cases, use ``tryGet()`` instead:

.. code:: php

   <?php

   var_dump(
       $url->tryGet('foo'),
       $url->tryGet('nonexistent'),
       $url->tryGet('nonexistent', 'custom-default-value')
   );

Output:

::

  string(3) "bar"
  NULL
  string(20) "custom-default-value"


Manipulating query parameters
=============================

Setting a single parameter
--------------------------

.. code:: php

   <?php

   $url->set('parameter', 'value');


Removing a single parameter
---------------------------

.. code:: php

   <?php

   $url->remove('foo');


Setting multiple parameters
---------------------------

.. code:: php

   <?php

   $url->add(['foo' => 'bar', 'lorem' => 'ipsum']);


Replacing all parameters
------------------------

.. code:: php

   <?php

   $url->setQuery(['foo' => 'bar']);


Removing all parameters
-----------------------

.. code:: php

   <?php

   $url->removeAll();


Building URLs
=============

.. NOTE::

   Building an URL with undefined scheme will yield a protocol-relative URL.

   Example: *//localhost/test*


Using ``build()`` or ``__toString()``
-------------------------------------

These methods will return an absolute or relative URL, depending on whether
the host is defined.

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = new Url();

   $url->setPath('/test');

   var_dump($url->build());

   $url->setScheme('http');
   $url->setHost('example.com');

   var_dump($url->build());

Output:

::

  string(5) "/test"
  string(23) "http://example.com/test"


Using ``buildAbsolute()``
-------------------------

This method will always return an absolute URL. If the host is not defined,
an exception will be thrown instead.

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = new Url();

   $url->setScheme('http');
   $url->setHost('example.com');
   $url->setPath('/test');

   var_dump($url->buildAbsolute());

Output:

::

  string(23) "http://example.com/test"


Using ``buildRelative()``
-------------------------

This method will always return a relative URL regardless of whether the host
is defined or not.

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = new Url();

   $url->setScheme('http');
   $url->setHost('example.com');
   $url->setPath('/test');

   var_dump($url->buildRelative());

Output:

::

  string(5) "/test"
