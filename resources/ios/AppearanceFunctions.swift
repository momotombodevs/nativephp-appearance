import Foundation
import UIKit

/// NativePHP bridge functions for application-local appearance control.
///
/// The preference is stored in `UserDefaults` and applied through each
/// window's `overrideUserInterfaceStyle`. It never changes the device-wide
/// setting.
enum AppearanceFunctions {

    /// Application-local appearance preference, as sent over the bridge.
    enum Mode: String {
        case system
        case light
        case dark

        static let storageKey = "nativephp_appearance.mode"

        /// The persisted preference, falling back to `system` for missing or corrupt values.
        static var stored: Mode {
            UserDefaults.standard.string(forKey: storageKey).flatMap(Mode.init(rawValue:)) ?? .system
        }

        /// `.unspecified` lets each window inherit the system appearance.
        var style: UIUserInterfaceStyle {
            switch self {
            case .system: .unspecified
            case .light: .light
            case .dark: .dark
            }
        }
    }

    /// Applies a style to every window of every connected scene, on the main thread.
    static func apply(_ style: UIUserInterfaceStyle) {
        let apply = {
            UIApplication.shared.connectedScenes
                .compactMap { $0 as? UIWindowScene }
                .flatMap(\.windows)
                .forEach { $0.overrideUserInterfaceStyle = style }
        }

        if Thread.isMainThread {
            apply()
        } else {
            DispatchQueue.main.sync(execute: apply)
        }
    }

    /// Validate, persist, and apply `system`, `light`, or `dark`.
    class Set: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let value = parameters["mode"] as? String,
                  let mode = Mode(rawValue: value) else {
                return BridgeResponse.error(code: "invalid_mode", message: "Appearance must be system, light, or dark.")
            }

            // Persist first so windows that appear during the change read the new value.
            UserDefaults.standard.set(mode.rawValue, forKey: Mode.storageKey)
            AppearanceFunctions.apply(mode.style)

            return BridgeResponse.success(data: ["mode": mode.rawValue])
        }
    }

    /// Return the persisted preference, defaulting to `system`.
    class Get: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(data: ["mode": Mode.stored.rawValue])
        }
    }

    fileprivate static var observers: [NSObjectProtocol] = []
}

/// Applies the stored preference at startup and keeps it applied.
///
/// NativePHP calls this before any window exists, so the preference is also
/// applied when a window becomes visible (before its first frame, avoiding a
/// flash of the system appearance) and whenever a scene activates.
func initializeAppearance() {
    let apply = {
        guard AppearanceFunctions.observers.isEmpty else { return }

        AppearanceFunctions.apply(AppearanceFunctions.Mode.stored.style)

        let center = NotificationCenter.default

        AppearanceFunctions.observers = [
            center.addObserver(forName: UIWindow.didBecomeVisibleNotification, object: nil, queue: .main) { notification in
                (notification.object as? UIWindow)?.overrideUserInterfaceStyle = AppearanceFunctions.Mode.stored.style
            },
            center.addObserver(forName: UIScene.didActivateNotification, object: nil, queue: .main) { _ in
                AppearanceFunctions.apply(AppearanceFunctions.Mode.stored.style)
            },
        ]
    }

    if Thread.isMainThread {
        apply()
    } else {
        DispatchQueue.main.async(execute: apply)
    }
}
