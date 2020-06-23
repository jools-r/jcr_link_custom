# jcr_link_custom

Adds up to five extra custom fields of up to 255 characters to the [Content › Links](http://docs.textpattern.io/administration/links-panel) panel and provides a corresponding tag to output the custom field.


## Use cases

Use whenever extra information needs to be stored with a link. For example:

- Store a txp image ID number and use it to attach a logo to a link.
- Store associated details, for example the location of a linked organisation.
- …


## Installation

Paste the code into the  *Admin › Plugins* panel, install and enable the plugin.


## Tags

### txp:jcr_link_custom

Outputs the content of the link custom field.

#### Tag attributes

`name`
Specifies the name of the link custom field. 
Example: Use `name="image_id"` to output the image_id custom field. Default: jcr_link_custom_1.

`escape`
Escape HTML entities such as `<`, `>` and `&` prior to echoing the field contents. 
Supports extended escape values in txp 4.8+
Example: Use `escape="textile"` to convert textile in the value. Default: none.

`default`
Specifies the default output if the custom field is empty
Example: Use `default="123"` to output "123", e.g. for use as the default image ID number. Default: empty.

`wraptag`
Wrap the custom field contents in an HTML tag
Example: Use `wraptag="h2"` to output `<h2>Custom field value</h2>`. Default: empty.

`class`
Specifies a class to be added to the `wraptag` attribure
Example: Use `wraptag="p" class="intro"` to output `<p class="intro">Custom field value</p>`. Default: empty

### txp:jcr_if_link_custom

Tests for existence of a link custom field, or whether one or several matches a value or pattern.

#### Tag attributes

`name`
Specifies the name of the link custom field. 
Example: Use `name="image_id"` to output the image_id custom field. Default: jcr_link_custom_1.

`value`
Value to test against (optional). 
If not specified, the tag tests for the existence of any value in the specified link custom field.
Example: Use `value="english"` to output only those links whose “language” link custom field is english. Default: none.

`match`
Match testing: exact, any, all, pattern. See the docs for [if_custom_field](https://docs.textpattern.com/tags/if_custom_field).
Default: exact.

`separator`
Item separator for match="any" or "all". Otherwise ignored
Default: empty.


## Example

Produce a list of linked partner logos (from link assigned to the link category "partners"):

```
<txp:linklist wraptag="ul" break="li" category="partners">
    <txp:jcr_if_link_custom name="image_id" value="">
        <a href="<txp:link_url />" title="<txp:link_name />"><txp:link_name /></a>
    <txp:else />
        <a href="<txp:link_url />" title="<txp:link_name />"><txp:image id='<txp:jcr_link_custom name="image_id" />' limit="1" /></a>
    </txp:jcr_if_link_custom>
</txp:linklist>
```

when the "image_id" link custom field is used to store the Image ID# of the logo.


## Changing the label of the custom field

The name of custom field can be changed by specifying a new label using the *Install from Textpack* field in the [Admin › Languages](http://docs.textpattern.io/administration/languages-panel) panel. Enter your own information in the following pattern and click **Upload**:

```
#@admin
#@language en-gb
jcr_link_custom_1 => Your label
jcr_link_custom_2 => Your other label
```

replacing `en-gb` with your own language and `Your label` with your own desired label.


## De-installation

The plugin cleans up after itself: deinstalling the plugin removes the extra column from the database. To stop using the plugin but keep the database tables, just disable (deactivate) the plugin but don't delete it.


## Changelog + Credits

### Changelog

- Version 0.2 – 2020/06/23 – Expand to handle multiple custom fields + migrate from v1
- Version 0.1.1 – 2016/12/0518 – Remedy table not being created on install 
- Version 0.1 – 2016/03/04 – First release


### Credits

Robert Wetzlmayr’s [wet_profile](https://github.com/rwetzlmayr/wet_profile) plugin for the starting point, and further examples by [Stef Dawson](http://www.stefdawson.com) and [Jukka Svahn](https://github.com/gocom). 