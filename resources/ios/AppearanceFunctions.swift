import Foundation
import UIKit

private func applyAppearance(_ style: UIUserInterfaceStyle) {
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

private func storedAppearanceStyle() -> UIUserInterfaceStyle {
    switch UserDefaults.standard.string(forKey: "nativephp_appearance.mode") {
    case "light": .light
    case "dark": .dark
    default: .unspecified
    }
}

func initializeAppearance() {
    applyAppearance(storedAppearanceStyle())

    NotificationCenter.default.addObserver(
        forName: UIScene.didActivateNotification,
        object: nil,
        queue: .main
    ) { _ in
        applyAppearance(storedAppearanceStyle())
    }
}

enum AppearanceFunctions {

    class Set: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let mode = parameters["mode"] as? String,
                  ["system", "light", "dark"].contains(mode) else {
                return BridgeResponse.error(code: "invalid_mode", message: "Appearance must be system, light, or dark.")
            }

            let style: UIUserInterfaceStyle = switch mode {
            case "light": .light
            case "dark": .dark
            default: .unspecified
            }

            applyAppearance(style)

            UserDefaults.standard.set(mode, forKey: "nativephp_appearance.mode")

            return BridgeResponse.success(data: ["mode": mode])
        }
    }

    class Get: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return BridgeResponse.success(data: [
                "mode": UserDefaults.standard.string(forKey: "nativephp_appearance.mode") ?? "system"
            ])
        }
    }
}
