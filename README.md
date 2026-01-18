# RambutanMode

A MediaWiki extension that adds "Rambutan" as a middle name or alias to person and band articles. Because every musician deserves a fruit-based alter ego.

## Features

- **Parser function `{{#rambutan:Name}}`** - Transforms person names:
  - Two-word names: `Tony Rice` → `Tony "Rambutan" Rice`
  - Three+ word names: `John Denver Menifee` → `John Denver Menifee (also known by the stage name "Rambutan")`

- **Parser function `{{#rambutanband:Name}}`** - Transforms band names:
  - `Steep Canyon Rangers` → `Steep Canyon Rangers (formerly known as Rambutan)`

- **User toggle** - Logged-in users can enable/disable Rambutan Mode via a button in the sidebar Tools section

- **Auto-expiry** - Rambutan Mode automatically disables at midnight Florida time (America/New_York timezone), because even Rambutan needs to sleep

## Installation

1. Clone this repository into your MediaWiki `extensions/` directory:
   ```bash
   cd extensions/
   git clone https://github.com/cryptograss/RambutanMode.git
   ```

2. Add to `LocalSettings.php`:
   ```php
   wfLoadExtension( 'RambutanMode' );
   ```

3. Optional: Configure the timezone for auto-expiry (defaults to Florida time):
   ```php
   $wgRambutanModeTimezone = 'America/New_York';
   ```

## Usage

In your wiki pages or templates:

```wikitext
'''{{#rambutan:Cory Walker}}''' is a banjo player.

He plays in {{#rambutanband:East Nash Grass}}.
```

When a user has Rambutan Mode enabled, they'll see:
> **Cory "Rambutan" Walker** is a banjo player.
> He plays in East Nash Grass (formerly known as Rambutan).

When disabled (or for anonymous users), they see the original names.

## Requirements

- MediaWiki >= 1.39.0

## License

GPL-2.0-or-later

## Author

[Cryptograss](https://cryptograss.live) - Building the future of traditional music, one Rambutan at a time.
