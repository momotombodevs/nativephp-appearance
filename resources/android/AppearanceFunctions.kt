package dev.momotombo.plugins.nativephp_appearance

import android.app.UiModeManager
import android.content.Context
import android.os.Build
import android.os.Looper
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

private const val TAG = "NativePHPAppearance"
private const val PREFERENCES = "nativephp_appearance"
private const val KEY_MODE = "mode"

/**
 * Application-local appearance preference, as sent over the bridge.
 *
 * [SYSTEM] maps to `MODE_NIGHT_AUTO`, which `setApplicationNightMode()`
 * treats as "follow the device setting".
 */
internal enum class AppearanceMode(val wire: String, val nightMode: Int) {
    SYSTEM("system", UiModeManager.MODE_NIGHT_AUTO),
    LIGHT("light", UiModeManager.MODE_NIGHT_NO),
    DARK("dark", UiModeManager.MODE_NIGHT_YES);

    companion object {
        fun fromWire(value: String?): AppearanceMode? = entries.firstOrNull { it.wire == value }
    }
}

/** Read the persisted preference, falling back to `system` for missing or corrupt values. */
internal fun storedAppearanceMode(context: Context): AppearanceMode =
    AppearanceMode.fromWire(
        context.getSharedPreferences(PREFERENCES, Context.MODE_PRIVATE).getString(KEY_MODE, null)
    ) ?: AppearanceMode.SYSTEM

/**
 * Apply an application-local night mode. Requires API 31; returns false when
 * the platform cannot apply it. It never changes the device-wide setting.
 */
internal fun applyAppearanceMode(context: Context, mode: AppearanceMode): Boolean {
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
        return false
    }

    val uiModeManager = context.getSystemService(UiModeManager::class.java) ?: return false
    uiModeManager.setApplicationNightMode(mode.nightMode)

    return true
}

/**
 * NativePHP bridge functions for application-local appearance control.
 *
 * The preference is stored in app-private SharedPreferences and applied
 * through [UiModeManager.setApplicationNightMode].
 */
object AppearanceFunctions {

    /** Validate, persist, and apply `system`, `light`, or `dark`. */
    class Set(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val mode = AppearanceMode.fromWire(parameters["mode"] as? String)
                ?: return BridgeResponse.error("invalid_mode", "Appearance must be system, light, or dark.")

            if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
                return BridgeResponse.error("unsupported", "Application appearance requires Android 12 (API 31) or newer.")
            }

            activity.getSharedPreferences(PREFERENCES, Context.MODE_PRIVATE)
                .edit()
                .putString(KEY_MODE, mode.wire)
                .apply()

            val apply = { applyAppearanceMode(activity, mode) }

            if (Looper.myLooper() == Looper.getMainLooper()) {
                apply()
            } else {
                activity.runOnUiThread { apply() }
            }

            return BridgeResponse.success(mapOf("mode" to mode.wire))
        }
    }

    /** Return the persisted preference, defaulting to `system`. */
    class Get(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            BridgeResponse.success(mapOf("mode" to storedAppearanceMode(context).wire))
    }
}

/**
 * Reapply the persisted preference when NativePHP initializes the app.
 *
 * Android also persists the application night mode itself; reapplying keeps
 * the system state aligned with the stored preference after reinstalls,
 * backups, or restores.
 */
fun initializeAppearance(context: Context) {
    try {
        applyAppearanceMode(context, storedAppearanceMode(context))
    } catch (exception: RuntimeException) {
        Log.w(TAG, "Unable to apply the stored appearance at startup.", exception)
    }
}
