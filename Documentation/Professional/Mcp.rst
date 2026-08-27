.. include:: ../Includes.txt

.. _professional-mcp:

=========
MCP tools
=========

Tonictypes Professional registers MCP tools (prefix `tonictypes_`) for agent
workflows. The MCP engine is provided by the Pro/license stack (`ns_t3af`);
tools are **not** available in Core-only installations.

Datatype tools
==============

============================== ===============================================
Tool                           Purpose
============================== ===============================================
`tonictypes_datatype_list`     List datatypes
`tonictypes_datatype_get`      Get one datatype
`tonictypes_datatype_create`   Create datatype
`tonictypes_datatype_update`   Update datatype metadata
`tonictypes_datatype_delete`   Delete datatype
`tonictypes_datatype_publish`  Ensure record table / TCA / optional classes
============================== ===============================================

Field tools
===========

`tonictypes_field_list|get|create|update|delete`

Record tools
============

`tonictypes_record_list|get|create|update|delete`

Typical agent flow
==================

1. Create/update fields
2. Create/update datatype and assign fields
3. **Publish** datatype (`tonictypes_datatype_publish`) so the DB table exists
4. Create/update records

.. note::

   Record tools require a published table. If you see “record table does not
   exist”, run publish first.

Safety tip
==========

Prefer reviewing schema impact in the backend table UI before publishing
destructive changes on production data. Enhanced “safe publish with impact
preview” for MCP is a planned Pro improvement, not required for 2.2.0.
