import SwiftUI

@main
struct PepBanAdminApp: App {
    @State private var auth = AuthManager()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environment(auth)
        }
    }
}

struct ContentView: View {
    @Environment(AuthManager.self) private var auth

    var body: some View {
        if auth.isAuthenticated {
            MainTabView()
        } else {
            LoginView()
        }
    }
}
