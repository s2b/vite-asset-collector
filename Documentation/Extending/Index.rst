..  include:: /Includes.rst.txt

..  _extending:

=========
Extending
=========

The extension can be adjusted to custom requirements in two ways:

*   :ref:`PSR-14 events <extending-events>` allow you to influence the
    behavior of the extension, for example how the Vite dev server is
    determined.
*   :ref:`Service interfaces <extending-interfaces>` allow you to replace
    or decorate the extension's core services with your own implementation.

..  toctree::
    :titlesonly:

    Events
    Interfaces
