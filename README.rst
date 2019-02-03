Url
###

Parsing, modifying and building URLs.

.. image:: https://travis-ci.com/kuria/url.svg?branch=master
   :target: https://travis-ci.com/kuria/url

.. contents::
   :depth: 3


Features
********

- parsing URLs
- building relative and absolute URLs, including protocol-relative URLs
- getting, checking and setting individual URL components:

  - scheme
  - host
  - port
  - path
  - query parameters
  - fragment


Requirements
************

- PHP 7.1+


Usage
*****

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

   $url = Url::parse('http://example.com:8080/test?foo=bar&lorem=ipsum#fragment');

.. TIP::

   If you wish to determine the current request URL, you may use the `kuria/request-info <https://github.com/kuria/request-info/>`_
   component, which integrates with ``kuria/url``.

.. NOTE::

   Parsing URLs that contain username and a password is supported, but these components are ignored.

   Such URLs are deprecated according to RFC 3986.


Getting URL components
======================

.. code:: php

   var_dump(
       $url->getScheme(),
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
       $url->hasHost(),
       $url->hasPort(),
       $url->hasPath(),
       $url->hasQuery(),
       $url->hasFragment()
   );


Output:

::

  string(4) "http"
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
       $url->get('lorem'),
       $url->get('nonexistent')
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
  NULL


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

Using ``build()`` or ``__toString()``
-------------------------------------

These methods will return an absolute or relative URL.

- if no host is specified, a relative URL will be returned
- if the host is specified, an absolute URL will be returned
  (unless the `preferred format option <Specifying a preferred format_>`_ is set to relative)

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


Specifying a preferred format
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

By default, ``build()`` and ``__toString()`` return an absolute URL if the host is specified.

This behavior can be changed by passing the ``$preferredFormat`` parameter to the constructor,
``Url::parse()`` or the ``setPreferredFormat()`` method.

- ``Url::RELATIVE`` - prefer generating a relative URL even if the host is specified
- ``Url::ABSOLUTE`` - prefer generating an absolute URL if a host is specified

.. code:: php

   <?php

   use Kuria\Url\Url;

   $url = Url::parse('http://example.com/foo');

   // print URL using the default preferred format (absolute)
   echo $url, "\n";

   // set the preferred format to relative
   $url->setPreferredFormat(Url::RELATIVE);

   echo $url, "\n";

Output:

::

  http://example.com/foo
  /foo


Using ``buildAbsolute()``
-------------------------

This method will always return an absolute URL.

If the host is not defined, ``Kuria\Url\Exception\IncompleteUrlException``
will be thrown.

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

.. NOTE::

   Building an absolute URL with undefined scheme will yield a protocol-relative URL.

   Example: *//localhost/test*


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
