# ScriptDetective - WordPress Script Manager 🔍

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

**Take full control of your WordPress scripts!** ScriptDetective helps you analyze, manage, and optimize script loading on your WordPress site.

![ScriptDetective Demo](https://raw.githubusercontent.com/carloscodees/scriptdetective/refs/heads/main/assets/img/Screenshot.png) <!-- Add actual screenshot later -->

## Features 🚀

- **Deep Page Scanning**  
  Discover all loaded scripts (WP-registered, themes, plugins, and external)
- **One-Click Script Control**  
  Enable/disable scripts per page/post with toggle switches
- **Advanced Script Analysis**  
  View dependencies, versions, file sizes, and loading position
- **Persistent Script Management**  
  Track disabled scripts even when they're not currently loaded
- **Performance Insights**  
  See script sizes (local and remote) with auto MB/KB conversion
- **Compatibility Mode**  
  Works with scripts loaded via non-standard methods (admin bar, inline, etc)
- **Smart Warnings**  
  Detect missing scripts and configuration conflicts

## Installation 📦

1. Download the [latest release](https://github.com/yourusername/scriptdetective/releases)
2. Upload the `scriptdetective` folder to `/wp-content/plugins/`
3. Activate through **Plugins** menu in WordPress
4. Look for the ScriptDetective meta box in post/page editors

## Usage 🛠️

1. **Scan Page**  
   Click "Full Page Scan" in the post editor to analyze scripts
   
2. **Toggle Scripts**  
   Use switches to enable/disable scripts individually
   
3. **Review Details**  
   Click arrow icons to expand script metadata:
   - File size
   - Dependencies
   - Loading position (header/footer)
   - Version info
   - Source type (WordPress/External)

4. **Persistent Control**  
   Disabled scripts remain managed even after page reloads

## Configuration ⚙️

Access settings via **Settings > ScriptDetective** (coming in v2.0):
- Global script blacklist/whitelist
- Auto-scan thresholds
- Cache management
- Script size calculation methods

## FAQ ❓

**Q: Why are some scripts still loading after disabling?**  
A: This could be due to:  
- Caching plugins/servers (clear cache)
- Scripts loaded via non-standard methods (enable Compatibility Mode)
- Theme/plugin updates resetting configurations

**Q: Does this work with page builders?**  
A: Yes! Tested with Elementor, Divi, and Gutenberg. Some dynamic content may require rescanning.

**Q: What happens if I disable a critical script?**  
A: The plugin shows warnings for dependencies. Test changes on staging first.

**Q: How are disabled scripts stored?**  
A: In post meta data. Disabled scripts persist through updates but can be removed via plugin uninstall.

## Support & Contributing 🤝

Found a bug? Have a feature request?  
[Open an issue](https://github.com/yourusername/scriptdetective/issues)

Want to contribute?  
1. Fork the repository  
2. Create your feature branch (`git checkout -b feature/awesome-feature`)  
3. Commit changes (`git commit -m 'Add awesome feature'`)  
4. Push to branch (`git push origin feature/awesome-feature`)  
5. Open Pull Request

## License 📄

This plugin is licensed under [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html).

---

**Optimize Your Site Today!** 🚀  
Take control of script loading and boost your WordPress performance with ScriptDetective.
