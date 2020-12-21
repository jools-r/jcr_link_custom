<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin["name"] = "jcr_link_custom";

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin["version"] = "0.2.3";
$plugin["author"] = "jcr / txpbuilders";
$plugin["author_uri"] = "http://txp.builders";
$plugin["description"] = "Adds multiple custom fields to the links panel";

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin["order"] = "5";

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin["type"] = "1";

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin["flags"] = "3";

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

// Customise the display of the custom field form labels by pasting the following into the Textpack box
// in Settings › Languages replacing the language code and field label names:
// #@owner jcr_link_custom
// #@language en, en-gb, en-us
// #@link
// jcr_link_custom_1 => Image ID
// jcr_link_custom_2 => Link status
// jcr_link_custom_3 => Page title
// jcr_link_custom_4 => Accent color
// jcr_link_custom_5 => Background color

$plugin['textpack'] = <<<EOT
#@owner jcr_link_custom
#@language en, en-gb, en-us
#@prefs
jcr_link_custom => Link custom fields
link_custom_1_set => Link custom field 1 name
link_custom_2_set => Link custom field 2 name
link_custom_3_set => Link custom field 3 name
link_custom_4_set => Link custom field 4 name
link_custom_5_set => Link custom field 5 name
#@language de
#@prefs
jcr_link_custom => Link Custom-Felder
link_custom_1_set => Name des 1. Link-Custom Feldes
link_custom_2_set => Name des 2. Link-Custom Feldes
link_custom_3_set => Name des 3. Link-Custom Feldes
link_custom_4_set => Name des 4. Link-Custom Feldes
link_custom_5_set => Name des 5. Link-Custom Feldes
EOT;
// End of textpack

if (!defined("txpinterface")) {
    @include_once("zem_tpl.php");
}

# --- BEGIN PLUGIN CODE ---
class jcr_link_custom
{
    /**
     * Initialise.
     */
    function __construct()
    {
        // Hook into the system's callbacks
        register_callback(array(__CLASS__, "lifecycle"), "plugin_lifecycle.jcr_link_custom");
        register_callback(array(__CLASS__, "ui"), "link_ui", "extend_detail_form");
        register_callback(array(__CLASS__, "save"), "link", "link_save");

        // Prefs pane for custom fields
        add_privs("prefs.jcr_link_custom", "1");

        // Redirect 'Options' link on plugins panel to preferences pane
        add_privs("plugin_prefs.jcr_link_custom", "1");
        register_callback(array(__CLASS__, "options_prefs_redirect"), "plugin_prefs.jcr_link_custom");
    }

  /**
   * Add and remove custom fields from txp_link table.
   *
   * @param $event string
   * @param $step string  The lifecycle phase of this plugin
   */
   public static function lifecycle($event, $step)
   {
        switch ($step) {
            case "enabled":
                add_privs("prefs.jcr_link_custom", "1");
                break;
            case "disabled":
                break;
            case "installed":
                // Add link custom fields to txp_link table
                $cols_exist = safe_query("SHOW COLUMNS FROM " . safe_pfx("txp_link") . " LIKE 'jcr_link_custom_%'");
                if (@numRows($cols_exist) == 0) {
                    safe_alter(
                        "txp_link",
                        "ADD COLUMN jcr_link_custom_1 VARCHAR(255) NOT NULL DEFAULT '',
                         ADD COLUMN jcr_link_custom_2 VARCHAR(255) NOT NULL DEFAULT '',
                         ADD COLUMN jcr_link_custom_3 VARCHAR(255) NOT NULL DEFAULT '',
                         ADD COLUMN jcr_link_custom_4 VARCHAR(255) NOT NULL DEFAULT '',
                         ADD COLUMN jcr_link_custom_5 VARCHAR(255) NOT NULL DEFAULT ''"
                    );
                }

                // Add prefs for link custom field names
                create_pref("link_custom_1_set", "", "jcr_link_custom", "0", "link_custom_set", "1");
                create_pref("link_custom_2_set", "", "jcr_link_custom", "0", "link_custom_set", "2");
                create_pref("link_custom_3_set", "", "jcr_link_custom", "0", "link_custom_set", "3");
                create_pref("link_custom_4_set", "", "jcr_link_custom", "0", "link_custom_set", "4");
                create_pref("link_custom_5_set", "", "jcr_link_custom", "0", "link_custom_set", "5");

                // Insert initial value for cf1 if none already exists (so that upgrade works)
                $cf_pref = get_pref("link_custom_1_set");
                if ($cf_pref === "") {
                    set_pref("link_custom_1_set", "custom1");
                }

                // Upgrade: Migrate v1 plugin legacy column
                $legacy = safe_query("SHOW COLUMNS FROM " . safe_pfx("txp_link") . " LIKE 'jcr_link_custom'");
                if (@numRows($legacy) > 0) {
                    // Copy contents of jcr_link_custom to jcr_link_custom_1 (where not NULL)
                    safe_update("txp_link", "`jcr_link_custom_1` = `jcr_link_custom`", "jcr_link_custom IS NOT NULL");
                    // Delete jcr_link_custom column
                    safe_alter("txp_link", "DROP COLUMN `jcr_link_custom`");
                    // Update language string (is seemingly not replaced by textpack)
                    safe_update("txp_lang", "data = 'Link custom fields', owner = 'jcr_link_custom'", "name = 'jcr_link_custom' AND lang = 'en'");
                    safe_update("txp_lang", "data = 'Link Custom-Felder', owner = 'jcr_link_custom'", "name = 'jcr_link_custom' AND lang = 'de'");
                }

                // Upgrade: Migrate from NULL to '' default value
                $has_nulls = safe_rows_start("*", "txp_link","`jcr_link_custom_1` IS NULL OR `jcr_link_custom_2` IS NULLOR `jcr_link_custom_3` IS NULL OR `jcr_link_custom_4` ISNULL OR `jcr_link_custom_5` IS NULL");
                if (@numRows($has_nulls) > 0) {
                    safe_update("txp_link", "jcr_link_custom_1 = ''", "jcr_link_custom_1 IS NULL");
                    safe_update("txp_link", "jcr_link_custom_2 = ''", "jcr_link_custom_2 IS NULL");
                    safe_update("txp_link", "jcr_link_custom_3 = ''", "jcr_link_custom_3 IS NULL");
                    safe_update("txp_link", "jcr_link_custom_4 = ''", "jcr_link_custom_4 IS NULL");
                    safe_update("txp_link", "jcr_link_custom_5 = ''", "jcr_link_custom_5 IS NULL");
                    safe_alter(
                        "txp_link",
                        "MODIFY jcr_link_custom_1  VARCHAR(255) NOT NULL DEFAULT '',
                         MODIFY jcr_link_custom_2  VARCHAR(255) NOT NULL DEFAULT '',
                         MODIFY jcr_link_custom_3  VARCHAR(255) NOT NULL DEFAULT '',
                         MODIFY jcr_link_custom_4  VARCHAR(255) NOT NULL DEFAULT '',
                         MODIFY jcr_link_custom_5  VARCHAR(255) NOT NULL DEFAULT ''"
                    );
                }
                break;
            case "deleted":
                // Remove columns from link table
                safe_alter(
                    "txp_link",
                    "DROP COLUMN jcr_link_custom_1,
                     DROP COLUMN jcr_link_custom_2,
                     DROP COLUMN jcr_link_custom_3,
                     DROP COLUMN jcr_link_custom_4,
                     DROP COLUMN jcr_link_custom_5"
                );
                // Remove all prefs from event 'jcr_link_custom'.
                remove_pref(null,"jcr_link_custom");

                // Remove all associated lang strings
                safe_delete(
                  "txp_lang",
                  "owner = 'jcr_link_custom'"
                );
                break;
        }
        return;
    }

    /**
     * Paint additional fields for link custom fields
     *
     * @param $event string
     * @param $step string
     * @param $dummy string
     * @param $rs array The current link's data
     * @return string
     */
    public static function ui($event, $step, $dummy, $rs)
    {
        global $prefs;

        extract(lAtts(array(
            "jcr_link_custom_1" => "",
            "jcr_link_custom_2" => "",
            "jcr_link_custom_3" => "",
            "jcr_link_custom_4" => "",
            "jcr_link_custom_5" => ""
        ), $rs, 0));

        $out = "";

        $cfs = preg_grep("/^link_custom_\d+_set/", array_keys($prefs));
        asort($cfs);

        foreach ($cfs as $name) {
            preg_match("/(\d+)/", $name, $match);

            if ($prefs[$name] !== "") {
                $out .= inputLabel("jcr_link_custom_" . $match[1], fInput("text", "jcr_link_custom_" . $match[1], ${"jcr_link_custom_" . $match[1]}, "", "", "", INPUT_REGULAR, "", "jcr_link_custom_" . $match[1]), "jcr_link_custom_" . $match[1]) . n;
            }
        }

        return $out;
    }

    /**
     * Save additional link custom fields
     *
     * @param $event string
     * @param $step string
     */
    public static function save($event, $step)
    {
        extract(doSlash(psa(array("jcr_link_custom_1", "jcr_link_custom_2", "jcr_link_custom_3", "jcr_link_custom_4", "jcr_link_custom_5", "id"))));
        $id = assert_int($id);
        safe_update(
            "txp_link",
            "jcr_link_custom_1 = '$jcr_link_custom_1',
             jcr_link_custom_2 = '$jcr_link_custom_2',
             jcr_link_custom_3 = '$jcr_link_custom_3',
             jcr_link_custom_4 = '$jcr_link_custom_4',
             jcr_link_custom_5 = '$jcr_link_custom_5'",
            "id = $id"
        );
    }

    /**
     * Renders a HTML link custom field.
     *
     * Can be altered by plugins via the 'prefs_ui > link_custom_set'
     * pluggable UI callback event.
     *
     * @param  string $name HTML name of the widget
     * @param  string $val  Initial (or current) content
     * @return string HTML
     * @todo   deprecate or move this when CFs are migrated to the meta store
     */
    public static function link_custom_set($name, $val)
    {
        return pluggable_ui("prefs_ui", "link_custom_set", text_input($name, $val, INPUT_REGULAR), $name, $val);
    }

    /**
     * Re-route 'Options' link on Plugins panel to Admin › Preferences panel
     *
     */
    public static function options_prefs_redirect()
    {
        header("Location: index.php?event=prefs#prefs_group_jcr_link_custom");
    }

}

if (txpinterface === "admin") {
    new jcr_link_custom();
}

// Register public tags (not restricted to public so that usable on dashboards)
if (class_exists("\Textpattern\Tag\Registry")) {
    Txp::get("\Textpattern\Tag\Registry")
        ->register("jcr_link_custom")
        ->register("jcr_if_link_custom");
}

/**
 * Gets a list of link custom fields.
 *
 * @return  array
 */
function jcr_get_link_custom_fields()
{
    global $prefs;
    static $out = null;
    // Have cache?
    if (!is_array($out)) {
        $cfs = preg_grep("/^link_custom_\d+_set/", array_keys($prefs));
        $out = array();
        foreach ($cfs as $name) {
            preg_match("/(\d+)/", $name, $match);
            if ($prefs[$name] !== "") {
                $out[$match[1]] = strtolower($prefs[$name]);
            }
        }
    }
    return $out;
}

/**
 * Maps 'txp_link' table's columns to article data values.
 *
 * This function returns an array of 'data-value' => 'column' pairs.
 *
 * @return array
 */
function jcr_link_column_map()
{
    $link_custom = jcr_get_link_custom_fields();
    $link_custom_map = array();

    if ($link_custom) {
        foreach ($link_custom as $i => $name) {
            $link_custom_map[$name] = "jcr_link_custom_".$i;
        }
    }

    return $link_custom_map;
}

/**
 * Public tag: Output custom link field
 * @param  string $atts[name] Name of custom field.
 * @param  string $atts[escape] Convert special characters to HTML entities.
 * @param  string $atts[default] Default output if field is empty.
 * @return string custom field output
 * <code>
 *        <txp:jcr_link_custom name="image_id" escape="html" />
 * </code>
 */
function jcr_link_custom($atts, $thing = null)
{
    global $thislink;

    assert_link();

    extract(lAtts(array(
        "class"   => "",
        "name"    => get_pref("link_custom_1_set"),
        "escape"  => null,
        "default" => "",
        "wraptag" => "",
    ), $atts));

    $name = strtolower($name);

    // Populate link custom field data;
    foreach (jcr_link_column_map() as $key => $column) {
        $thislink[$key] = isset($column) ? $column : null;
    }

    if (!isset($thislink[$name])) {
        trigger_error(gTxt("field_not_found", array("{name}" => $name)), E_USER_NOTICE);
        return "";
    }
    $cf_num = $thislink[$name];
    $cf_val = $thislink[$cf_num];

    if (!isset($thing)) {
        $thing = $cf_val !== "" ? $cf_val : $default;
    }

    $thing = ($escape === null ? txpspecialchars($thing) : parse($thing));

    return !empty($thing) ? doTag($thing, $wraptag, $class) : "";
}

/**
 * Public tag: Check if custom link field exists
 * @param  string $atts[name]    Name of custom field.
 * @param  string $atts[value]   Value to test against (optional).
 * @param  string $atts[match]   Match testing: exact, any, all, pattern.
 * @param  string $atts[separator] Item separator for match="any" or "all". Otherwise ignored.
 * @return string custom field output
 * <code>
 *        <txp:jcr_if_link_custom name="image_id" /> … <txp:else /> … </txp:jcr_if_link_custom>
 * </code>
 */
function jcr_if_link_custom($atts, $thing = null)
{
    global $thislink;

    assert_link();

    extract($atts = lAtts(array(
        "name"      => get_pref("link_custom_1_set"),
        "value"     => null,
        "match"     => "exact",
        "separator" => "",
    ), $atts));

    $name = strtolower($name);

    // Populate link custom field data;
    foreach (jcr_link_column_map() as $key => $column) {
        $thislink[$key] = isset($column) ? $column : null;
    }

    if (!isset($thislink[$name])) {
        trigger_error(gTxt("field_not_found", array("{name}" => $name)), E_USER_NOTICE);
        return "";
    }
    $cf_num = $thislink[$name];
    $cf_val = $thislink[$cf_num];

    if ($value !== null) {
        $cond = txp_match($atts, $cf_val);
    } else {
        $cond = $cf_val !== "";
    }

    return isset($thing) ? parse($thing, !empty($cond)) : !empty($cond);
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---

# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. jcr_link_custom

Adds up to five extra custom fields of up to 255 characters to the "Content › Links":http://docs.textpattern.io/administration/links-panel panel along with corresponding tags to output the custom field and to test if it contains a value or matches a specific value.

h3. Use cases

Use whenever extra information needs to be stored with a link. For example:

* Store a txp image ID number and use it to attach a logo to a link.
* Store associated details, for example the location of a linked organisation.
* …


h2. Installation / Deinstallation

h3. Installation

Paste the @.txt@ installer code into the _Admin › Plugins_ panel, or upload the plugin's @.php@ file via the _Upload plugin_ button, then install and enable the plugin.

h3. De-installation

The plugin cleans up after itself: deinstalling (deleting) the plugin removes the extra columns from the database as well as custom field names and labels. To stop using the plugin but keep the custom field data in the database, just disable (deactivate) the plugin but don't delete it.


h2. Plugin tags

h3. jcr_link_custom

Outputs the content of the link custom field.

h4. Tag attributes

@name@
Specifies the name of the link custom field.
Example: Use @name="image_id"@ to output the image_id custom field. Default: jcr_link_custom_1.

@escape@
Escape HTML entities such as @<@, @>@ and @&@ prior to echoing the field contents.
Supports extended escape values in txp 4.8+
Example: Use @escape="textile"@ to convert textile in the value. Default: none.

@default@
Specifies the default output if the custom field is empty
Example: Use @default="123"@ to output "123", e.g. for use as the default image ID number. Default: empty.

@wraptag@
Wrap the custom field contents in an HTML tag
Example: Use @wraptag="h2"@ to output @<h2>Custom field value</h2>@. Default: empty.

@class@
Specifies a class to be added to the @wraptag@ attribute
Example: Use @wraptag="p" class="intro"@ to output @<p class="intro">Custom field value</p>@. Default: empty

h3. jcr_if_link_custom

Tests for existence of a link custom field, or whether one or several matches a value or pattern.

h4. Tag attributes

@name@
Specifies the name of the link custom field.
Example: Use @name="image_id"@ to output the image_id custom field. Default: jcr_link_custom_1.

@value@
Value to test against (optional).
If not specified, the tag tests for the existence of any value in the specified link custom field.
Example: Use @value="english"@ to output only those links whose “language” link custom field is english. Default: none.

@match@
Match testing: exact, any, all, pattern. See the docs for "if_custom_field":https://docs.textpattern.com/tags/if_custom_field.
Default: exact.

@separator@
Item separator for match="any" or "all". Otherwise ignored.
Default: empty.


h2. Examples

h3. Example 1

Produce a list of linked partner logos (from link assigned to the link category "partners"):

bc. <txp:linklist wraptag="ul" break="li" category="partners">
    <txp:jcr_if_link_custom name="image_id" value="">
        <a href="<txp:link_url />" title="<txp:link_name />"><txp:link_name /></a>
    <txp:else />
        <a href="<txp:link_url />" title="<txp:link_name />"><txp:image id='<txp:jcr_link_custom name="image_id" />' limit="1" /></a>
    </txp:jcr_if_link_custom>
</txp:linklist>

p. where the @image_id@ link custom field is used to store the Image ID# of the logo.


h2. Custom field labels

The label displayed alongside the custom field in the edit image panel can be changed by specifying a new label using the _Install from Textpack_ field in the "Admin › Languages":http://docs.textpattern.io/administration/languages-panel.html panel. Enter your own information in the following pattern and click *Upload*:

bc. #@owner jcr_link_custom
#@language en, en-gb, en-us
#@link
jcr_link_custom_1 => Your label
jcr_link_custom_2 => Your other label

p. replacing @en@ with your own language and @Your label@ with your own desired label.


h2. Changelog and credits

h3. Changelog

* Version 0.2.4 – 2020/12/20 – Align with other custom field plugins
* Version 0.2.3 – 2020/06/29 – Tag registration + textpack fixes
* Version 0.2.2 – 2020/06/27 – Handle migration from previous versions of the plugin on install
* Version 0.2.1 – 2020/06/27 – Fix for missing custom_field name vs. missing value for cf
* Version 0.2 – 2020/06/23 – Expand to handle multiple custom fields + migrate from v1
* Version 0.1.1 – 2016/12/0518 – Remedy table not being created on install
* Version 0.1 – 2016/03/04 – First release

h3. Credits

Robert Wetzlmayr’s "wet_profile":https://github.com/rwetzlmayr/wet_profile plugin for the starting point, and further examples by "Stef Dawson":http://www.stefdawson.com and "Jukka Svahn":https://github.com/gocom.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
