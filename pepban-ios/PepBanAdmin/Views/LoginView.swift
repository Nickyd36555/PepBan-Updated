import SwiftUI

struct LoginView: View {
    @Environment(AuthManager.self) private var auth
    @State private var password = ""
    @State private var totpCode = ""
    @State private var phase: Phase = .password
    @State private var isLoading = false
    @State private var errorMessage: String?

    enum Phase {
        case password
        case totp(savedPassword: String)
    }

    var body: some View {
        ZStack {
            Color(.systemGroupedBackground).ignoresSafeArea()

            VStack(spacing: 32) {
                VStack(spacing: 12) {
                    Image(systemName: "shield.fill")
                        .font(.system(size: 64))
                        .foregroundStyle(.red)
                    Text("PepBan Admin")
                        .font(.largeTitle.bold())
                    Text("pepban.com")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
                .padding(.top, 60)

                VStack(spacing: 16) {
                    switch phase {
                    case .password:
                        SecureField("Admin password", text: $password)
                            .textContentType(.password)
                            .inputFieldStyle()
                            .onSubmit { submitPassword() }

                    case .totp:
                        VStack(spacing: 8) {
                            Text("Two-Factor Authentication")
                                .font(.headline)
                            Text("Enter the 6-digit code from your authenticator app.")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                                .multilineTextAlignment(.center)
                        }
                        TextField("000000", text: $totpCode)
                            .textContentType(.oneTimeCode)
                            .keyboardType(.numberPad)
                            .multilineTextAlignment(.center)
                            .font(.system(size: 28, weight: .semibold, design: .monospaced))
                            .inputFieldStyle()
                            .onChange(of: totpCode) { _, v in
                                totpCode = String(v.filter(\.isNumber).prefix(6))
                                if totpCode.count == 6 { submitTotp() }
                            }
                        Button("Use a different account") {
                            phase = .password
                            totpCode = ""
                            errorMessage = nil
                        }
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    }

                    if let err = errorMessage {
                        Text(err)
                            .font(.footnote)
                            .foregroundStyle(.red)
                            .multilineTextAlignment(.center)
                    }

                    Button(action: {
                        switch phase {
                        case .password: submitPassword()
                        case .totp:     submitTotp()
                        }
                    }) {
                        HStack {
                            if isLoading { ProgressView().tint(.white) }
                            else { Text(buttonLabel).fontWeight(.semibold) }
                        }
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(.red)
                        .foregroundStyle(.white)
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                    }
                    .disabled(isLoading || submitDisabled)
                }
                .padding(.horizontal, 24)

                Spacer()
            }
        }
    }

    private var buttonLabel: String {
        switch phase {
        case .password: return "Sign In"
        case .totp:     return "Verify"
        }
    }

    private var submitDisabled: Bool {
        switch phase {
        case .password: return password.isEmpty
        case .totp:     return totpCode.count != 6
        }
    }

    private func submitPassword() {
        guard !password.isEmpty else { return }
        isLoading = true
        errorMessage = nil
        Task {
            do {
                try await auth.login(password: password)
            } catch APIError.totpRequired {
                phase = .totp(savedPassword: password)
                errorMessage = nil
            } catch {
                errorMessage = error.localizedDescription
            }
            isLoading = false
        }
    }

    private func submitTotp() {
        guard case .totp(let savedPassword) = phase, totpCode.count == 6 else { return }
        isLoading = true
        errorMessage = nil
        Task {
            do {
                try await auth.login(password: savedPassword, totpCode: totpCode)
            } catch {
                errorMessage = error.localizedDescription
                totpCode = ""
            }
            isLoading = false
        }
    }
}

private extension View {
    func inputFieldStyle() -> some View {
        self
            .padding()
            .background(Color(.systemBackground))
            .clipShape(RoundedRectangle(cornerRadius: 12))
            .shadow(color: .black.opacity(0.06), radius: 4, y: 2)
    }
}
