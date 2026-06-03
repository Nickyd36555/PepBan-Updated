import SwiftUI

struct MainTabView: View {
    @Environment(AuthManager.self) private var auth
    @State private var showLogoutAlert = false

    var body: some View {
        TabView {
            DashboardView()
                .tabItem { Label("Dashboard", systemImage: "chart.bar.fill") }

            BannedListView()
                .tabItem { Label("Banned", systemImage: "xmark.shield.fill") }

            ClientsListView()
                .tabItem { Label("Clients", systemImage: "building.2.fill") }

            DisputesView()
                .tabItem { Label("Disputes", systemImage: "exclamationmark.bubble.fill") }

            AuditView()
                .tabItem { Label("Audit", systemImage: "list.bullet.clipboard.fill") }
        }
        .tint(.red)
    }
}
