=== Abschussplan HGMH - Hunting Harvest Management ===
Contributors: foe05
Donate link: https://github.com/foe05/pr25_one
Tags: hunting, harvest, management, hegegemeinschaft, wildlife, tracking, german
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Comprehensive hunting harvest tracking system for German Hegegemeinschaften with advanced permission management and wildart-specific configuration.

== Description ==

**Abschussplan HGMH** is a specialized WordPress plugin designed for German hunting associations (Hegegemeinschaften) to efficiently track and manage hunting harvest data across multiple hunting districts (Jagdbezirke) and species groups (Wildarten).

= 🎯 Perfect for German Hegegemeinschaften =

* **Wildart-specific Management**: Configure different Meldegruppen for Rotwild, Damwild, Rehwild, Schwarzwild, etc.
* **3-Level Permission System**: Besucher (public), Obmann (group leaders), Vorstand (board members)
* **Master-Detail Administration**: Intuitive left-sidebar navigation for wildart configuration
* **Flexible Limits System**: Choose between meldegruppe-specific or total hegegemeinschaft limits
* **Public CSV Export**: Automatic data sharing without authentication requirements

= 🆕 New in Version 2.2.0 =

**Master-Detail Backend Interface**
Configure wildart-specific meldegruppen with an intuitive admin interface featuring:
- Left-sidebar wildart navigation
- Right-panel detail editing
- Real-time auto-save functionality
- Responsive design for mobile/tablet use

**Advanced Permission Management**
- **Besucher**: Public access to summary statistics
- **Obmann**: Wildart-specific meldegruppe assignments with data filtering
- **Vorstand**: Full administrative access to all features

**Enhanced Limits Management**
- **Mode A**: Meldegruppen-specific limits with individual SOLL values
- **Mode B**: Hegegemeinschaft total limits for simplified management
- **Status Badges**: Visual indicators (🟢🟡🔴🔥) for limit monitoring

**Improved CSV Export System**
- Admin backend CSV export section
- URL generator for automated systems
- All existing export URLs remain fully functional
- Public access preserved for external integrations

= 📊 Key Features =

**Data Submission & Tracking**
* Hunting harvest data entry with species, categories, and hunting districts
* User-based data filtering for Obmann role assignments
* Real-time data validation and submission tracking

**Flexible Reporting System**
* Public summary statistics with customizable parameters
* Export functionality with date range and species filtering
* Configurable filename patterns for automated workflows

**Administrative Interface** 
* Modern tabbed interface with comprehensive CRUD operations
* User management with wildart-specific meldegruppe assignments
* Database management tools with bulk operations
* Migration support from previous versions

**Multi-Database Support**
* Primary: WordPress MySQL (default)
* Extended: SQLite and PostgreSQL compatibility
* Optimized indexes for large-scale hunting associations

= 🔧 Technical Specifications =

**WordPress Integration**
* Native WordPress user system integration
* WordPress security standards (nonce verification, capability checks)
* Responsive design with Bootstrap 5.3 framework
* AJAX-powered interface for seamless user experience

**Security Features**
* 3-level permission hierarchy with fine-grained access control
* SQL injection prevention with prepared statements
* XSS protection with WordPress escaping functions
* CSRF protection for all administrative actions

**Performance Optimization**
* Database indexes for permission-based queries
* Object caching for complex hegegemeinschaft structures
* Batch processing for large datasets
* Mobile-first responsive design

= 📱 Shortcodes =

**[abschuss_form]** - Data Submission Form
* Automatic meldegruppe preselection for assigned Obmanns
* Species-specific validation
* Parameter support: `wildart="Rotwild"`

**[abschuss_table]** - Data Display Table
* User-based filtering for Obmann assignments
* Pagination and sorting capabilities
* Parameters: `wildart="Rotwild"` `meldegruppe="Nord"` `limit="50"`

**[abschuss_summary]** - Statistics Summary
* Public access for harvest statistics
* Flexible species and meldegruppe filtering
* Parameters: `wildart="Rotwild"` `meldegruppe="Nord"` `show_details="true"`

**[abschuss_admin]** - Admin Interface (Vorstand only)
* Complete data management interface
* User assignment and configuration tools
* Full administrative capabilities

**[abschuss_limits]** - Limits Management (Vorstand only)
* Configure hunting limits and quotas
* Switch between limit modes
* Real-time status monitoring

= 🌍 German Hegegemeinschaft Integration =

This plugin is specifically designed for the German hunting system:

* **Wildarten**: Rotwild, Damwild, Rehwild, Schwarzwild, Raubwild
* **Meldegruppen**: Regional hunting group organization  
* **Jagdbezirke**: Individual hunting district management
* **Abschussplanung**: Harvest planning and tracking
* **SOLL/IST Vergleich**: Target vs. actual harvest comparison

= 💼 Use Cases =

**Small Hegegemeinschaft (2-3 Wildarten)**
* Simple hegegemeinschaft total limits
* Basic meldegruppe structure
* Streamlined administration

**Large Hegegemeinschaft (5+ Wildarten, 10+ Meldegruppen)**  
* Meldegruppen-specific limits with detailed tracking
* Multiple Obmann assignments across wildarten
* Complex permission structures
* Advanced reporting capabilities

**Multi-Year Data Management**
* Historical harvest data tracking
* Year-over-year comparison reports
* Long-term planning support
* Data export for external analysis

== Installation ==

= Automatic Installation =

1. Navigate to **Plugins → Add New** in your WordPress admin
2. Search for "Abschussplan HGMH"
3. Click **Install Now** and then **Activate**
4. Go to **Abschussplan HGMH** in your admin menu to configure

= Manual Installation =

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/abschussplan-hgmh/`  
3. Activate through **Plugins** menu in WordPress
4. Configure via **Abschussplan HGMH** admin page

= Initial Setup for Hegegemeinschaften =

**Step 1: Configure Wildarten**
* Navigate to **Admin Interface → Wildarten-Konfiguration**
* Default species (Rotwild, Damwild) are pre-configured
* Add additional species as needed

**Step 2: Set Up Meldegruppen**
* Use the Master-Detail interface to configure meldegruppen per wildart
* Example: Rotwild → Meldegruppe_Nord, Meldegruppe_Süd, Meldegruppe_Ost

**Step 3: Configure Limits**
* Choose between meldegruppen-specific or hegegemeinschaft total limits
* Set SOLL values for categories (Böcke, Alttiere, Kälber, etc.)

**Step 4: Assign Obleute**
* Navigate to **Obleute** tab in admin interface
* Assign WordPress users to specific meldegruppe/wildart combinations
* Example: User "Max Mustermann" → Meldegruppe_Nord for Rotwild

**Step 5: Add Shortcodes to Pages**
```
[abschuss_form] - For data submission
[abschuss_table] - For data display  
[abschuss_summary] - For public statistics
```

== Frequently Asked Questions ==

= Is this plugin suitable for non-German hunting organizations? =

While specifically designed for German Hegegemeinschaften, the plugin can be adapted for other hunting management systems. The terminology and workflow are optimized for German hunting associations.

= Can I migrate from version 2.1.0 to 2.2.0? =

Yes! The plugin includes automatic migration from v2.1.0 to v2.2.0 with:
* Zero-downtime upgrade process
* Full backward compatibility for existing CSV export URLs
* Preservation of all existing data and configurations
* Default permission assignments for existing users

= How do CSV exports work with the new permission system? =

CSV export functionality remains **completely unchanged** for backward compatibility:
* All existing export URLs continue to work without authentication
* Public access preserved via `wp_ajax_nopriv_export_abschuss_csv`
* Parameter filtering (species, meldegruppe, date ranges) unchanged
* New admin CSV export section provides additional URL generation tools

= What happens to existing users when upgrading to v2.2.0? =

* **Administrators**: Retain full access to all features including new Master-Detail interface
* **Other Users**: Receive standard permissions - administrators must manually assign Obmann roles
* **No Data Loss**: All existing submissions and configurations are preserved
* **Workflow Impact**: Existing workflows continue unchanged, new features are additive

= Can Obleute access data from multiple wildarten? =

Yes! The permission system supports wildart-specific assignments:
* One user can be Obmann for "Meldegruppe_Nord" in Rotwild
* Same user can be Obmann for "Meldegruppe_Süd" in Damwild  
* Permissions are evaluated per wildart for maximum flexibility

= How do I set up automated CSV exports? =

Use the admin CSV export section to generate URLs:
```
# Example URLs (remain publicly accessible)
wp-admin/admin-ajax.php?action=export_abschuss_csv&species=Rotwild
wp-admin/admin-ajax.php?action=export_abschuss_csv&species=Rotwild&from=2024-01-01&to=2024-12-31
```

= What are the performance requirements for large Hegegemeinschaften? =

The plugin is optimized for complex structures:
* **Small Setup**: 2-3 wildarten, 3-5 meldegruppen → No special requirements
* **Large Setup**: 5+ wildarten, 10+ meldegruppen → Recommended: PHP 8.0+, MySQL 8.0+
* **Database**: Automatic index optimization for permission-based queries
* **Frontend**: Responsive design works on mobile, tablet, and desktop

= Can I customize the status badge colors and thresholds? =

Currently, status badges use fixed thresholds:
* 🟢 Green: < 80% of limit  
* 🟡 Yellow: 80-95% of limit
* 🔴 Red: 95-110% of limit
* 🔥 Fire: > 110% of limit

Future versions may include customizable thresholds.

= How do I backup my hunting data before plugin updates? =

**Recommended Backup Process:**
1. WordPress database backup (via plugin or hosting provider)
2. Export CSV data via admin interface as additional backup
3. Document current configuration (wildarten, meldegruppen, user assignments)
4. Test plugin update on staging environment first

= What browsers are supported for the Master-Detail interface? =

**Fully Supported:**
* Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

**Mobile Support:**  
* iOS Safari 14+, Chrome Mobile 90+, Samsung Internet 13+

**Features:** 
* Responsive design with sidebar collapsing on tablets
* Touch-friendly controls for mobile users
* ES5-compatible JavaScript for broader browser support

== Screenshots ==

1. **Master-Detail Admin Interface** - Wildart-specific meldegruppen configuration with intuitive left-sidebar navigation
2. **Permission Management** - Obmann assignment interface with wildart-specific meldegruppe selection
3. **Advanced Limits Management** - Flexible limits system with two modes and real-time status badges
4. **Data Submission Form** - User-friendly form with automatic meldegruppe preselection for assigned Obleute
5. **Statistics Dashboard** - Comprehensive overview with harvest data and limit status monitoring
6. **CSV Export Interface** - Admin backend CSV export section with URL generator for automated systems
7. **Mobile Responsive Design** - Master-Detail interface optimized for mobile and tablet use
8. **Public Summary Display** - Frontend statistics view accessible to all visitors

== Changelog ==

= 2.2.0 (2024-08-19) =

**🎯 Major Release: Hegegemeinschafts-Verwaltung**

**New Features:**
* **Master-Detail Backend Interface** - Intuitive wildart-specific meldegruppen configuration
* **3-Level Permission System** - Besucher, Obmann, Vorstand with fine-grained access control
* **Advanced Limits Management** - Choose between meldegruppen-specific or hegegemeinschaft total limits
* **Enhanced Admin CSV Export** - URL generator and direct download functionality  
* **Obmann User Management** - Assign users to specific meldegruppe/wildart combinations
* **Wildart-Specific Configuration** - Left-sidebar navigation with right-panel detail editing

**Improvements:**
* **Performance Optimization** - Database indexes for complex hegegemeinschaft structures
* **Responsive Design** - Mobile-first approach with sidebar collapsing on tablets
* **Auto-Save Functionality** - Real-time saving of configuration changes
* **Status Badge System** - Visual limit monitoring with 🟢🟡🔴🔥 indicators
* **Enhanced Security** - Permission-based access control for all administrative functions

**Technical:**
* **Database Migration** - Automatic upgrade from v2.1.0 with rollback capability
* **Backward Compatibility** - All existing CSV export URLs continue to work
* **Code Quality** - WordPress Coding Standards compliance
* **Accessibility** - WCAG 2.1 AA compliance for new UI elements

**Bug Fixes:**
* Fixed JavaScript compatibility issues with older browsers
* Resolved CSS conflicts with other plugins using Bootstrap
* Improved error handling for database operations
* Enhanced validation for user input in Master-Detail interface

= 2.1.0 =
* Enhanced CSV export functionality
* Improved admin interface
* Performance optimizations
* Bug fixes and security improvements

= 2.0.0 = 
* Complete rewrite with modern WordPress standards
* Bootstrap 5.3 integration
* AJAX-powered interface
* Multi-database support

= 1.x.x =
* Initial release versions
* Basic hunting harvest tracking
* Simple admin interface

== Upgrade Notice ==

= 2.2.0 =
**Major update with Master-Detail backend and permission system.** Fully backward compatible - all existing functionality preserved. Automatic migration from v2.1.0 included. New features available immediately after update. Recommended for all users.

= 2.1.0 = 
Enhanced functionality and security improvements. Update recommended.

= 2.0.0 =
Complete rewrite with modern standards. Backup recommended before upgrade.
