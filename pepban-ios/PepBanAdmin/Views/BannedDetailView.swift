import SwiftUI

struct BannedDetailView: View {
    let customerId: Int
    @State private var response: BannedDetailResponse?
    @State private var isLoading = true
    @State private var error: String?
    @State private var editingNotes = ""
    @State private var isSaving = false
    @State private var actionError: String?
    @State private var showStatusSheet = false
    @Environment(\.dismiss) private var dismiss

    var customer: BannedDetail? { response?.customer }

    var body: some View {
        Group {
            if isLoading {
                ProgressView("Loading…")
            } else if let err = error {
                ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
            } else if let c = customer {
                List {
                    Section("Identity") {
                        LabeledContent("Email", value: c.email)
                        if !c.firstName.isEmpty || !c.lastName.isEmpty {
                            LabeledContent("Name", value: "\(c.firstName) \(c.lastName)".trimmingCharacters(in: .whitespaces))
                        }
                        if !c.phone.isEmpty { LabeledContent("Phone", value: c.phone) }
                        if !c.ipAddress.isEmpty { LabeledContent("IP", value: c.ipAddress) }
                        if !c.billingAddress.isEmpty { LabeledContent("Address", value: c.billingAddress) }
                    }

                    Section("Status") {
                        HStack {
                            Text("Status")
                            Spacer()
                            StatusBadge(status: c.status)
                        }
                        LabeledContent("Reports", value: "\(c.reportsCount) from \(c.storeCount) stores")
                        LabeledContent("Reason", value: c.reason.isEmpty ? "—" : c.reason)
                        LabeledContent("Added", value: shortDate(c.dateAdded))
                    }

                    Section("Admin Notes") {
                        TextEditor(text: $editingNotes)
                            .frame(minHeight: 80)
                        Button(isSaving ? "Saving…" : "Save Notes") {
                            saveNotes()
                        }
                        .disabled(isSaving || editingNotes == c.adminNotes)
                    }

                    Section("Actions") {
                        if let err = actionError {
                            Text(err).foregroundStyle(.red).font(.footnote)
                        }
                        Button("Change Status") { showStatusSheet = true }
                    }

                    if let reports = response?.reports, !reports.isEmpty {
                        Section("Ban Reports (\(reports.count))") {
                            ForEach(reports) { r in
                                VStack(alignment: .leading, spacing: 2) {
                                    Text(r.siteUrl ?? "Unknown store")
                                        .font(.footnote.bold())
                                    if !r.reason.isEmpty {
                                        Text(r.reason)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    Text(shortDate(r.dateReported))
                                        .font(.caption2)
                                        .foregroundStyle(.secondary)
                                }
                                .padding(.vertical, 2)
                            }
                        }
                    }
                }
                .confirmationDialog("Change Status", isPresented: $showStatusSheet) {
                    Button("Set Active", role: .none) { updateStatus("active") }
                    Button("Set Inactive") { updateStatus("inactive") }
                    Button("Set Pending") { updateStatus("pending") }
                    Button("Cancel", role: .cancel) {}
                }
            }
        }
        .navigationTitle("Customer Detail")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        do {
            response = try await APIClient.shared.bannedDetail(id: customerId)
            editingNotes = response?.customer.adminNotes ?? ""
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }

    private func saveNotes() {
        isSaving = true
        Task {
            do {
                try await APIClient.shared.updateBanned(id: customerId, adminNotes: editingNotes)
            } catch {
                actionError = error.localizedDescription
            }
            isSaving = false
        }
    }

    private func updateStatus(_ status: String) {
        Task {
            do {
                try await APIClient.shared.updateBanned(id: customerId, status: status)
                await load()
            } catch {
                actionError = error.localizedDescription
            }
        }
    }

    private func shortDate(_ str: String) -> String {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd HH:mm:ss"
        guard let d = f.date(from: str) else { return str }
        let out = DateFormatter()
        out.dateStyle = .medium
        out.timeStyle = .short
        return out.string(from: d)
    }
}
