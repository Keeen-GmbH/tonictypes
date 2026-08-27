.. include:: ../Includes.txt

.. _field-types-custom:

=======================
Custom field types
=======================

You can register additional field types via TypoScript.

.. code-block:: typoscript

   plugin.tx_tonictypes.fieldtypes {
     customfieldtype {
       class = Vendor\Extension\Tca\Field\CustomFieldtype
       icon = EXT:extension/Resources/Public/Icons/Field/customfieldtype.gif
       label = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:type.customfieldtype
       flexform = EXT:extension/Configuration/FlexForms/Field/CustomFieldtype.xml
       # optional value post-processor
       # value = Vendor\Extension\Form\Value\CustomFieldtype
     }
   }

Requirements
============

* Field class must implement `\K3n\Tonictypes\Tca\FieldInterface`
  (typically extend `\K3n\Tonictypes\Tca\AbstractField`)
* Optional value class extends `\K3n\Tonictypes\Form\Value\AbstractValue`
  and implements `\K3n\Tonictypes\Form\Value\ValueInterface`
* FlexForm config is read with `$this->getField()->getConfig('nodeName')`

After registration, clear caches and select the new type when creating a field.
