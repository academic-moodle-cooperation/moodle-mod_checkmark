Checkmark Add-ons
=================

Install Checkmark add-on subplugins in this directory. Each add-on lives in its
own subdirectory and uses the Frankenstyle component name
`checkmarkaddon_<name>`.

Example:

```text
mod/checkmark/addon/randomselect/version.php
mod/checkmark/addon/randomselect/lang/en/checkmarkaddon_randomselect.php
```

The add-on must at least provide a `version.php` file with
`$plugin->component = 'checkmarkaddon_<name>';`.
