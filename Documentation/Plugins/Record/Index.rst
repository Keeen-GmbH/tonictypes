.. include:: ../../Includes.txt

.. _recordplugin:

.. image:: ../../Images/logo_tonictypes.jpg
   :width: 250px

Display Records Plugin
----------------------

This is the main plugin for displaying records through Tonictypes.
It can be used to display either records in a list or show detailed
information of a record. It delivers different methods for displaying
records.

.. image:: ../../Images/Screenshots/Plugins/plugin_display_records_wizard.jpg

Configuration
~~~~~~~~~~~~~

.. confval:: Plugin Type

   This selects the type of which data is injected to the fluid template.
   It can be one of the following:

   +-------------------------+---------------------------------------------------------------------------------------------+
   | **List**                | Multiple Records injected to ``{records}``                                                  |
   +-------------------------+---------------------------------------------------------------------------------------------+
   | **Detail**              | Fixed Record from selection is injected to ``{record}``                                     |
   +-------------------------+---------------------------------------------------------------------------------------------+
   | **Dynamic Detail**      | Dynamic injected record from Url Parameter to ``{record}``                                  |
   +-------------------------+---------------------------------------------------------------------------------------------+
   | **Raw Fluid**           | Use the power of TYPO3 Fluid as usual without implementing a record through some selection. |
   |                         | This option lets you show fluid code and injects variables without records                  |
   +-------------------------+---------------------------------------------------------------------------------------------+


.. confval:: Datatype

   Please select a datatype for the records you want to show in the plugin.

.. confval:: Record

    This configuration only shows up in the single record context plugin type, such as ``List`` or ``Detail``

.. confval:: Page for Detail View

   Records can be linked to a detail page which has a "Dynamic Detail" Plugin.
   The Detail View PageId is configurable with fluid conditions. A configured detail page will be used when the
   according condition is valid.

   Conditions are valid when they are empty or the expression is valid.

   To link a record, you can use a Tonictypes ViewHelper:

.. code-block:: html

	   <dv:link.record record="{record}" pageUid="{detailPid}" additionalParams="{paramOne:'One'}">{record.title}</t:link.record>

See the ViewHelpers section for more information about the available Tonictypes ViewHelpers.

.. confval:: Record Storage Page

   Please select the record storage page where the records are stored.


Field/Value Filter Settings
###########################

.. confval:: Available Markers

    If you selected variables to inject, you will get an information here,
    of what markers are available for you fluid code.

.. confval:: Filter Condition

    You can build a filter for selecting the records, that will be
    shown in the frontend. Adding filters will modify the Query Builder
    to fetch only the data, you want.

.. confval:: Condition for activating the filter (Fluid)

    Please enter a filter condition, when the filter needs to
    be activated under different circumstances. When leaving the
    condition empty, the filter is automatically activated.

    Conditions are valid when they are empty or the expression is valid.

.. image:: ../../Images/Screenshots/Plugins/filters.jpg


Repository Settings
###################

.. confval:: Include hidden

    Check this to include hidden records

.. confval:: Recursive Include Record Storage Pages of selected pages

    When checked, every record on subpages of the selected Record Storage Pages will also
    be included in the result.

.. confval:: Limit

    Limits the count of displayed record to the value, you have configured in this field.

.. confval:: Sorting

    You can configure multiple sortings by adding new configurations in this field.
    Each sorting configuration can be activated though a variable (e.g. GET/POST) and
    offers you the possibility to create multiple sorting configurations that can be
    combined.

.. confval:: Condition for activating the filter (Fluid)

    Please enter a filter condition, when the filter needs to
    be activated under different circumstances. When leaving the
    condition empty, the filter is automatically activated.

    Conditions are valid when they are empty or the expression is valid.

Here is an example on how to configure a default sorting and
changing the sorting direction, when the page gets an parameter "argument"
``?argument=title-down`` (The variable "argument" has to be created and assigned
in the plugin settings)

.. image:: ../../Images/Screenshots/Plugins/sorting.jpg


Template Settings
#################

.. confval:: Template Selection

   The fluid templates for Tonictypes. Record(s) and Variables will be injected automatically.
   You can either manually select a template file or select a predefined template.
   There is also a way to switch templates on certain conditions with the help of the template variables.

+----------------------------------+----------------------------------------------------------+
| Template Selection               | Description                                              |
+==================================+==========================================================+
| Debug Template                   | Show debugging information (Default)                     |
+----------------------------------+----------------------------------------------------------+
| Select a custom template path    | You can select a file in your filesystem                 |
+----------------------------------+----------------------------------------------------------+
| Enter custom fluid code          | The code, you can enter in the Fluid Field               |
|                                  | will be used for rendering                               |
+----------------------------------+----------------------------------------------------------+
| Your configured template         | If you configured templates in TypoScript, these will    |
|                                  | appear in the dropdown after the above mentioned options |
+----------------------------------+----------------------------------------------------------+

.. confval:: Render this Template without Sitetemplate

	If this option is set, the complete output of this plugin will showed without rendering the whole
	site template. Everything else, located on this page will be ignored in the output.

.. confval:: Template Switch

	This setting gives you the possibility to use different templates on conditions. The condition
	is written in fluid. If a condition matches, the selected template will be used instead of the
	above selected template in 'Template Selection'.

.. confval:: Variable Injection

   Select the variables, that will be injected into the fluid template.
   When condition-fields are shown, you will get an information above, what
   variables are available.

Overrides
#########

The overrides can be used to override plugin setting with variables.
When a variable exists and contains information, the override will
directly be used.

Developer Settings
##################

.. confval:: Debug

    When activated, debugging information about the SQL Query is shown above the
    rendered page.

.. confval:: Custom Headers

	With this option, you are able to send and overwrite the headers of the response. This
	adds the possibility to generate XML or JSON Files if you set the ``Content-Type`` header or
	to force a download by using ``Content-Disposition``