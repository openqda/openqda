# OpenQDA Architecture
In terms of software architecture OpenQDA is a pluggable monolith. That means,
there is a core application that can be extended by internal or external programs
that follow a certain interface definition.

A plugin can therefore be a php module (backend), a JavaScript module or Vue component (frontend)
or an external service (both).

One of the main goals of this architecture is to make it easy for you to extend OpenQDA
without the need to know its exact internals but by following only a few rules that
a plugin must comply with.