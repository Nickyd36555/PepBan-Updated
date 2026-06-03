import SwiftUI

struct LoginView: View {
    @Environment(AuthManager.self) private var auth
    @State private var password = ""
    @State private var isLoading = false
    @State private var errorMessage: String?

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
                    SecureField("Admin password", text: $password)
                        .textContentType(.password)
                        .padding()
                        .background(Color(.systemBackground))
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                        .shadow(color: .black.opacity(0.06), radius: 4, y: 2)
                        .onSubmit { login() }

                    if let err = errorMessage {
                        Text(err)
                            .font(.footnote)
                            .foregroundStyle(.red)
                            .multilineTextAlignment(.center)
                    }

                    Button(action: login) {
                        HStack {
                            if isLoading {
                                ProgressView().tint(.white)
                            } else {
                                Text("Sign In")
                                    .fontWeight(.semibold)
                            }
                        }
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(.red)
                        .foregroundStyle(.white)
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                    }
                    .disabled(password.isEmpty || isLoading)
                }
                .padding(.horizontal, 24)

                Spacer()
            }
        }
    }

    private func login() {
        guard !password.isEmpty else { return }
        isLoading = true
        errorMessage = nil
        Task {
            do {
                try await auth.login(password: password)
            } catch {
                errorMessage = error.localizedDescription
            }
            isLoading = false
        }
    }
}
