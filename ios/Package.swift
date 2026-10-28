// swift-tools-version: 5.9
import PackageDescription
import Foundation

let core = ProcessInfo.processInfo.environment["PAM_NATIVE_CORE"] ?? "../../pam-native"
let package = Package(
    name: "PamNativeDrawer",
    platforms: [.iOS(.v15)],
    products: [.library(name: "PamNativeDrawer", targets: ["PamNativeDrawer"])],
    dependencies: [.package(path: core + "/ios")],
    targets: [
        .target(name: "PamNativeDrawer", dependencies: [.product(name: "PamNative", package: "ios")], path: "Sources"),
    ]
)
