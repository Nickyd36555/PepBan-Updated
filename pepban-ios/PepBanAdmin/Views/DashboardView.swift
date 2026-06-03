import SwiftUI

struct DashboardView: View {
    @Environment(AuthManager.self) private var auth
    @State private var stats: DashboardStats?
    @State private var isLoading = true
    @State private var error: String?

    var body: some View {
        NavigationStack {
            Group {
                if isLoading {
                    ProgressView("Loading…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let err = error {
                    ContentUnavailableView("Could not load", systemImage: "exclamationmark.triangle", description: Text(err))
                } else if let s = stats {
                    ScrollView {
                        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 16) {
                            StatCard(value: s.activeBans, label: "Active Bans", icon: "xmark.shield.fill", color: .red)
                            StatCard(value: s.activeClients, label: "Active Clients", icon: "building.2.fill", color: .blue)
                            StatCard(value: s.totalReports, label: "Total Reports", icon: "doc.text.fill", color: .orange)
                            StatCard(value: s.newBans30d, label: "New (30d)", icon: "clock.fill", color: .purple)
                            StatCard(value: s.openDisputes, label: "Open Disputes", icon: "exclamationmark.bubble.fill", color: .yellow)
                        }
                        .padding()
                    }
                }
            }
            .navigationTitle("Dashboard")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button("Sign Out", role: .destructive) {
                        Task { await auth.logout() }
                    }
                    .foregroundStyle(.red)
                }
                ToolbarItem(placement: .topBarLeading) {
                    Button(action: load) {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
        }
        .task { load() }
    }

    private func load() {
        isLoading = true
        error = nil
        Task {
            do {
                stats = try await APIClient.shared.dashboard()
            } catch {
                self.error = error.localizedDescription
            }
            isLoading = false
        }
    }
}

struct StatCard: View {
    let value: Int
    let label: String
    let icon: String
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Image(systemName: icon)
                .font(.title2)
                .foregroundStyle(color)
            Text("\(value)")
                .font(.system(size: 34, weight: .bold, design: .rounded))
            Text(label)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color(.systemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 16))
        .shadow(color: .black.opacity(0.06), radius: 6, y: 2)
    }
}
