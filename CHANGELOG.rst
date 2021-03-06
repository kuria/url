Changelog
#########


5.0.0
*****

- ``Url`` is no longer ``final``
- added the preferred format option, which affects the output of ``Url::build()``
- removed username and password support (see RFC 3986)


4.0.0
*****

- removed the following ``Url`` methods: ``current()``, ``setCurrent()``,
  ``setDefaultCurrentHost()`` and ``clearCurrentUrlCache()``
  (use the `kuria/request-info <https://github.com/kuria/request-info/>`_ component instead)
- ``Url::buildAbsolute()`` now throws an exception if the host is not defined
  (instead of defaulting to the current host)


3.1.0
*****

- added ``Url::setCurrent()``


3.0.0
*****

- ``Url`` is now ``final``
- ``Url::buildAbsolute()`` now uses the current host instead of throwing ``IncompleteUrlException``
- added ``Url::setDefaultCurrentHost()``, removed the parameter from ``Url::current()``
- changed class members from protected to private
- cs fixes, added codestyle checks


2.0.0
*****

- merged ``get()`` and ``tryGet()``, removed custom defaults


1.0.0
*****

Initial release
