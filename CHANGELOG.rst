Changelog
#########

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
