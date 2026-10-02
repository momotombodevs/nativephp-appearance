package dev.momotombo.plugins.nativephp_appearance

import android.app.UiModeManager
import android.content.Context
import android.os.Build
import android.os.Looper
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

object AppearanceFunctions {

    private fun applyNightMode(activity: FragmentActivity, nightMode: Int) {
        val apply = {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                activity.getSystemService(UiModeManager::class.java).setApplicationNightMode(nightMode)
            }
        }

        if (Looper.myLooper() == Looper.getMainLooper()) {
            apply()
        } else {
            activity.runOnUiThread(apply)
        }
    }

    class Set(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val mode = parameters["mode"] as? String
                ?: return BridgeResponse.error("invalid_mode", "An appearance mode is required.")

            val nightMode = when (mode) {
                "system" -> UiModeManager.MODE_NIGHT_AUTO
                "light" -> UiModeManager.MODE_NIGHT_NO
                "dark" -> UiModeManager.MODE_NIGHT_YES
                else -> return BridgeResponse.error("invalid_mode", "Appearance must be system, light, or dark.")
            }

            applyNightMode(activity, nightMode)

            activity.getSharedPreferences("nativephp_appearance", Context.MODE_PRIVATE)
                .edit()
                .putString("mode", mode)
                .apply()

            return BridgeResponse.success(mapOf("mode" to mode))
        }
    }

    class Get(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val mode = context.getSharedPreferences("nativephp_appearance", Context.MODE_PRIVATE)
                .getString("mode", "system") ?: "system"

            return BridgeResponse.success(mapOf("mode" to mode))
        }
    }
}

fun initializeAppearance(context: Context) {
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
        return
    }

    val mode = context.getSharedPreferences("nativephp_appearance", Context.MODE_PRIVATE)
        .getString("mode", "system") ?: "system"
    val nightMode = when (mode) {
        "light" -> UiModeManager.MODE_NIGHT_NO
        "dark" -> UiModeManager.MODE_NIGHT_YES
        else -> UiModeManager.MODE_NIGHT_AUTO
    }

    context.getSystemService(UiModeManager::class.java).setApplicationNightMode(nightMode)
}
