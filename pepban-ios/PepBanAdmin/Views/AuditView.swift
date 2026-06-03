import SwiftUI

struct AuditView: View {
    @State private var entries: [AuditEntry] = []
    @State private var page = 1
    @State private var totalPages = 1
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && entries.isEmpty {
                    ProgressView("Loading…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let err = error, entries.isEmpty {
                    ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
                } else if entries.isEmpty {
                    ContentUnavailableView("No activity yet", systemImage: "list.bullet.clipboard")
                } else {
                    List {
                        ForEach(entries) { e in
                            AuditRow(entry: e)
                        }
                        if page < totalPages {
                            Button("Load more") { loadMore() }
                                .frame(maxWidth: .infinity)
                                .foregroundStyle(.red)
                        }
                    }
                }
            }
            .navigationTitle("Audit Log")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button(action: { reset() }) {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .task { await load() }
        }
    }

    private func reset() {
        page = 1
        entries = []
        Task { await load() }
    }

    private func loadMore() {
        page += 1
        Task { await load() }
    }

    private func load() async {
        isLoading = true
        do {
            let result = try await APIClient.shared.auditLog(page: page)
            if page == 1 { entries = result.entries }
            else { entries.append(contentsOf: result.entries) }
            totalPages = result.pages
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }
}

struct AuditRow: View {
    let entry: AuditEntry

    var actionColor: Color {
        switch entry.action {
        case "ban_add", "api_report":    return .red
        case "ban_delete":               return .purple
        case "status_change":            return .orange
        case "dispute_resolve":          return .green
        case "dispute_dismiss":          return .gray
        default:                         return .blue
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 3) {
            HStack {
                Text(entry.action.replacingOccurrences(of: "_", with: " ").capitalized)
                    .font(.footnote.bold())
                    .foregroundStyle(actionColor)
                Spacer()
                Text(relativeTime(entry.createdAt))
                    .font(.caption2)
                    .foregroundStyle(.tertiary)
            }
            Text(entry.actor)
                .font(.caption)
                .foregroundStyle(.secondary)
            if !entry.details.isEmpty {
                Text(entry.details)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .lineLimit(2)
            }
        }
        .padding(.vertical, 3)
    }

    private func relativeTime(_ str: String) -> String {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd HH:mm:ss"
        guard let d = f.date(from: str) else { return str }
        let formatter = RelativeDateTimeFormatter()
        formatter.unitsStyle = .abbreviated
        return formatter.localizedString(for: d, relativeTo: Date())
    }
}
