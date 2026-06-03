import SwiftUI

struct AddBanView: View {
    let onAdded: () -> Void
    @Environment(\.dismiss) private var dismiss
    @State private var email = ""
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var phone = ""
    @State private var reason = ""
    @State private var isSubmitting = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            Form {
                Section("Required") {
                    TextField("Email address", text: $email)
                        .keyboardType(.emailAddress)
                        .autocorrectionDisabled()
                        .textInputAutocapitalization(.never)
                }

                Section("Optional") {
                    TextField("First name", text: $firstName)
                    TextField("Last name", text: $lastName)
                    TextField("Phone", text: $phone)
                        .keyboardType(.phonePad)
                    TextField("Reason for ban", text: $reason, axis: .vertical)
                        .lineLimit(3...6)
                }

                if let err = errorMessage {
                    Section {
                        Text(err)
                            .foregroundStyle(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("Add Ban")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button(isSubmitting ? "Adding…" : "Add") {
                        submit()
                    }
                    .disabled(email.isEmpty || isSubmitting)
                }
            }
        }
    }

    private func submit() {
        isSubmitting = true
        errorMessage = nil
        let ban = NewBan(email: email, firstName: firstName, lastName: lastName, phone: phone, reason: reason)
        Task {
            do {
                _ = try await APIClient.shared.addBan(ban)
                onAdded()
                dismiss()
            } catch {
                errorMessage = error.localizedDescription
            }
            isSubmitting = false
        }
    }
}
